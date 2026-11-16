<?php

class MediaRecordNotFoundException extends RuntimeException {}

function managed_media_path(mixed $path): ?string
{
    if (!is_string($path)) {
        return null;
    }
    $path = ltrim($path, '/');
    if (!preg_match('~\A(?:public/uploads/(news|gallery|staff|achievements|facilities|extracurriculars|profile)/(?:thumbs/)?\1-[0-9]{14}-[a-f0-9]{16}\.(?:jpg|jpeg|png|webp)|storage/uploads/(documents|accreditation)/\2-[0-9]{14}-[a-f0-9]{16}\.pdf|public/uploads/videos/videos-[0-9]{14}-[a-f0-9]{16}\.(?:mp4|mov))\z~D', $path)) {
        return null;
    }
    return $path;
}

function media_local_path(string $relative): ?string
{
    if (managed_media_path($relative) === null) {
        return null;
    }
    $root = APP_ROOT;
    $path = $root . '/' . $relative;
    $directory = dirname($path);
    while ($directory !== $root) {
        if (is_link($directory)) {
            return null;
        }
        $directory = dirname($directory);
    }
    $canonicalDirectory = realpath(dirname($path));
    return is_link($path) ? null : ($canonicalDirectory ? $canonicalDirectory . DIRECTORY_SEPARATOR . basename($path) : $path);
}

function media_cleanup_directory(): string
{
    $storage = APP_ROOT . '/storage';
    $directory = $storage . '/media-cleanup';
    if (is_link($storage) || is_link($directory)
        || (!is_dir($directory) && !@mkdir($directory, 0700, true))) {
        throw new RuntimeException('Penyimpanan file belum tersedia. Silakan coba lagi.');
    }
    return $directory;
}

function media_lifecycle_lock(): void
{
    if (is_resource($GLOBALS['media_lifecycle_lock'] ?? null)) {
        return;
    }
    $path = media_cleanup_directory() . '/lifecycle.lock';
    $handle = is_link($path) ? false : @fopen($path, 'c');
    if (!$handle || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Penyimpanan file sedang tidak tersedia. Silakan coba lagi.');
    }
    // Hold through request shutdown: another writer must not collect an in-flight upload.
    $GLOBALS['media_lifecycle_lock'] = $handle;
}

function media_cleanup_enqueue(string $relative): void
{
    if (managed_media_path($relative) === null) {
        return;
    }
    media_lifecycle_lock();
    $job = media_cleanup_directory() . '/' . hash('sha256', $relative) . '.json';
    if (is_file($job) && !is_link($job)) {
        $existing = json_decode((string) @file_get_contents($job), true);
        if (($existing['path'] ?? null) === $relative) {
            return;
        }
    }
    $handle = is_link($job) ? false : @fopen($job, 'xb');
    if (!$handle) {
        throw new RuntimeException('Cleanup file belum dapat dijadwalkan. Silakan coba lagi.');
    }
    $bytes = json_encode(['path'=>$relative], JSON_THROW_ON_ERROR);
    $ok = fwrite($handle, $bytes) === strlen($bytes) && fflush($handle);
    fclose($handle);
    if (!$ok) {
        @unlink($job);
        throw new RuntimeException('Cleanup file belum dapat dijadwalkan. Silakan coba lagi.');
    }
}

function media_cleanup_forget(string $relative): void
{
    $job = media_cleanup_directory() . '/' . hash('sha256', $relative) . '.json';
    if (is_file($job) && !is_link($job) && !@unlink($job)) {
        error_log('Media cleanup journal removal failed; reference check will protect persisted media.');
    }
}

function media_paths_in_value(mixed $value): array
{
    $paths = [];
    if (is_array($value)) {
        foreach ($value as $child) {
            $paths = array_merge($paths, media_paths_in_value($child));
        }
    } elseif (($path = managed_media_path($value)) !== null) {
        $paths[] = $path;
        if (str_starts_with($path, 'public/') && !str_starts_with($path, 'public/uploads/videos/') && !str_contains($path, '/thumbs/')) {
            $paths[] = dirname($path) . '/thumbs/' . basename($path);
        }
    }
    return array_values(array_unique($paths));
}

function media_reference_paths($pdo): array
{
    $settings = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $paths = [];
    foreach (['news'=>'image_path', 'gallery_items'=>'image_path', 'staff_members'=>'photo_path',
        'school_achievements'=>'image_path', 'extracurriculars'=>'image_path',
        'school_facilities'=>'image_path', 'school_documents'=>'file_path'] as $table=>$column) {
        $rows = $pdo->query('SELECT `' . $column . '` FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        $paths = array_merge($paths, media_paths_in_value($rows));
    }
    foreach ($settings as $setting) {
        $paths = array_merge($paths, media_paths_in_value(json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR)));
    }
    // Retain only historical file references; never read applicant identity or collect PPDB directories.
    $paths = array_merge($paths, media_paths_in_value($pdo->query('SELECT family_card_path, birth_certificate_path, report_card_path, photo_path FROM ppdb_applications')->fetchAll()));
    return array_fill_keys($paths, true);
}

function retry_media_cleanup($pdo): void
{
    try {
        media_lifecycle_lock();
        $jobs = glob(media_cleanup_directory() . '/*.json') ?: [];
        if (!$jobs) {
            return;
        }
        $references = media_reference_paths($pdo);
        foreach ($jobs as $job) {
            if (is_link($job)) {
                continue;
            }
            $value = json_decode((string) file_get_contents($job), true, 512, JSON_THROW_ON_ERROR);
            $relative = managed_media_path($value['path'] ?? null);
            if ($relative === null || basename($job) !== hash('sha256', $relative) . '.json') {
                continue;
            }
            $local = media_local_path($relative);
            if ($local === null || isset($references[$relative]) || isset($GLOBALS['pending_uploaded_files'][$local])) {
                continue;
            }
            clearstatcache(true, $local);
            if (!file_exists($local) || (is_file($local) && @unlink($local))) {
                media_cleanup_forget($relative);
            } else {
                error_log('Media cleanup deferred; an unreferenced owned file could not be removed.');
            }
        }
    } catch (Throwable $exception) {
        // Fail closed on reference verification; never undo a completed database commit.
        error_log('Media cleanup deferred because references or private storage could not be verified.');
    }
}

function require_pending_media_files(): void
{
    foreach ($GLOBALS['pending_uploaded_files'] ?? [] as $path=>$unused) {
        clearstatcache(true, $path);
        if (is_link($path) || !is_file($path) || filesize($path) <= 0) {
            throw new RuntimeException('File baru gagal disimpan. Data lama tetap dipertahankan.');
        }
    }
}

function require_new_media_reference(mixed $value): void
{
    foreach (media_paths_in_value($value) as $relative) {
        $local = media_local_path($relative);
        if ($local !== null) {
            clearstatcache(true, $local);
        }
        if ($local === null || !is_file($local) || filesize($local) <= 0) {
            throw new RuntimeException('File baru gagal disimpan. Data lama tetap dipertahankan.');
        }
    }
}

function require_transactional_media_table($pdo, string $table): void
{
    $engine = $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $table . "'")->fetchColumn();
    if (!is_string($engine) || strcasecmp($engine, 'InnoDB') !== 0) {
        throw new RuntimeException('Penyimpanan data belum mendukung operasi file yang aman.');
    }
}

function execute_media_mutation($pdo, string $sql, array $data, string $table, string $column): int
{
    media_lifecycle_lock();
    $columns = ['news'=>'image_path', 'gallery_items'=>'image_path', 'staff_members'=>'photo_path',
        'school_achievements'=>'image_path', 'extracurriculars'=>'image_path',
        'school_facilities'=>'image_path', 'school_documents'=>'file_path'];
    if (($columns[$table] ?? null) !== $column) {
        throw new LogicException('Invalid media repository mapping.');
    }
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Operasi file memerlukan transaksi tersendiri.');
    }
    $commitAttempted = false;
    try {
        require_transactional_media_table($pdo, $table);
        $pdo->beginTransaction();
        $oldPath = null;
        if (isset($data['id'])) {
            $select = $pdo->prepare('SELECT `' . $column . '` FROM `' . $table . '` WHERE id = :id FOR UPDATE');
            $select->execute(['id'=>$data['id']]);
            $row = $select->fetch();
            if (!$row) {
                throw new MediaRecordNotFoundException('Data tidak ditemukan. Tidak ada perubahan yang disimpan.');
            }
            $oldPath = $row[$column];
            if (!isset($data[$column]) || $row[$column] !== $data[$column]) {
                foreach (media_paths_in_value($row[$column]) as $old) {
                    media_cleanup_enqueue($old);
                }
            }
        }
        if (isset($data[$column]) && $data[$column] !== $oldPath) {
            require_new_media_reference($data[$column]);
        }
        require_pending_media_files();
        $statement = $pdo->prepare($sql);
        $statement->execute($data);
        if (str_starts_with($sql, 'DELETE FROM ') && $statement->rowCount() !== 1) {
            throw new MediaRecordNotFoundException('Data tidak ditemukan. Tidak ada data yang dihapus.');
        }
        $savedId = isset($data['id']) ? (int) $data['id'] : (int) $pdo->lastInsertId();
        require_pending_media_files();
        $commitAttempted = true;
        $GLOBALS['media_commit_in_progress'] = true;
        $pdo->commit();
    } catch (Throwable $exception) {
        $rollbackConfirmed = true;
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $rollbackException) {
            $rollbackConfirmed = false;
            error_log('Media transaction outcome could not be verified; new files retained for reference-checked retry.');
        }
        if ($commitAttempted || !$rollbackConfirmed) {
            // Connection loss during commit has an ambiguous outcome; retain the journal for reference-checked retry.
            $GLOBALS['pending_uploaded_files'] = [];
        } else {
            rollback_uploaded_files();
        }
        $GLOBALS['media_commit_in_progress'] = false;
        throw $exception;
    }
    commit_uploaded_files();
    retry_media_cleanup($pdo);
    return $savedId;
}
