<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/stage3_database_preflight.php';

function cleanup_require(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cleanup_within(string $path, string $root): bool
{
    $path = realpath($path);
    $root = realpath($root);
    return is_string($path) && is_string($root)
        && str_starts_with(strtolower($path), strtolower(rtrim($root, '/\\') . DIRECTORY_SEPARATOR));
}

function cleanup_original_hashes(string $project): array
{
    $hashes = [];
    foreach (['config/database.php','config/app.php','database/schema.sql','templates/header.php'] as $file) {
        $hashes[$file] = hash_file('sha256', $project . '/' . $file);
    }
    foreach (['public/uploads','storage/uploads'] as $folder) {
        if (!is_dir($project . '/' . $folder)) {
            continue;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project . '/' . $folder, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $hashes[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
    }
    ksort($hashes);
    return $hashes;
}

try {
    $mode = $argv[1] ?? '';
    cleanup_require(in_array($mode, ['--dry-run','--apply'], true), 'Use --dry-run or --apply.');
    $project = dirname(__DIR__);
    $private = realpath($project . '/storage/testing/stage3-mariadb');
    cleanup_require(is_string($private) && cleanup_within($private, $project . '/storage/testing'), 'Unverified private testing root.');
    $pdo = stage3_test_database();
    $tables = array_merge(array_keys(require __DIR__ . '/fixtures/stage3_content_tables.php'),
        ['admins','site_settings','admin_activity_logs','ppdb_applications']);
    $counts = [];
    if (in_array('ppdb_entries', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true)) {
        $tables[] = 'ppdb_entries';
    }
    foreach ($tables as $table) {
        $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }
    // Runner cleanup has already removed its exact owned rows. Never broaden this to DELETE/TRUNCATE.
    cleanup_require(array_sum($counts) === 0, 'Testing database has unexpected residual rows; inspect ownership before deleting anything.');
    foreach ([8087,8088,8089] as $port) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.2);
        if (is_resource($socket)) {
            fclose($socket);
            throw new RuntimeException('Testing HTTP server still running; refuse file cleanup.');
        }
    }
    $originalHashes = cleanup_original_hashes($project);
    $plan = [];
    $preserved = [];
    $snapshots = 0;
    foreach (glob($private . '/http-*', GLOB_ONLYDIR) as $snapshot) {
        $name = basename($snapshot);
        cleanup_require(preg_match('/^http-(auth3|crud3|role3|upload3|download3|input3|consistency3|error3)_[a-f0-9]{12}$/', $name) === 1,
            'Unknown snapshot directory: stop.');
        cleanup_require(cleanup_within($snapshot, $private) && !is_link($snapshot), 'Snapshot path escapes testing root.');
        $metadataPath = $snapshot . '/manifest.json';
        if (str_starts_with($name, 'http-auth3_') && !is_file($metadataPath)) {
            $metadataPath = $private . '/auth-http-run.json';
        }
        $metadata = json_decode(file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR);
        cleanup_require(($metadata['tag'] ?? null) === substr($name, 5)
            && realpath($metadata['snapshot'] ?? '') === realpath($snapshot)
            && is_file($snapshot . '/result.json'), 'Snapshot ownership metadata mismatch.');
        $snapshots++;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($snapshot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
            cleanup_require(cleanup_within($file->getPathname(), $snapshot) && !is_link($file->getPathname()), 'Untrusted link/path in snapshot: stop.');
            if (!$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($snapshot) + 1));
            $category = null;
            foreach (['public/uploads/'=>'uploaded_dummy', 'storage/uploads/'=>'uploaded_dummy',
                'input-fixtures/'=>'input_fixture', 'test-private/'=>'outside_directory_canary',
                'storage/media-cleanup/'=>'cleanup_fixture',
                'storage/sessions/'=>'session_fixture', 'storage/tmp/'=>'temporary_fixture'] as $prefix=>$kind) {
                if (str_starts_with($relative, $prefix) && $file->getFilename() !== '.htaccess') {
                    $category = $kind;
                    break;
                }
            }
            if (in_array($relative, ['fixture.pdf','fixture-backup.png','consistency-fixture.png','consistency-invalid-image.txt','safe-control.php'], true)) {
                $category = 'input_fixture';
            }
            if (!str_contains($relative, '/') && (str_ends_with($relative, '.cookies') || $relative === 'cookies.txt')) {
                $category = 'cookie_fixture';
            }
            if ($category !== null) {
                $plan[] = ['path'=>$file->getPathname(),'relative'=> $name . '/' . $relative,
                    'category'=>$category,'bytes'=>$file->getSize(),'sha256'=>hash_file('sha256', $file->getPathname()),'snapshot'=>$snapshot];
            } elseif (preg_match('/\.(json|log)$/', $relative) || $file->getFilename() === '.htaccess') {
                $preserved[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
    }
    cleanup_require($snapshots > 0, 'No verified test snapshots found.');
    $categories = [];
    $bytes = 0;
    foreach ($plan as $item) {
        $categories[$item['category']] = ($categories[$item['category']] ?? 0) + 1;
        $bytes += $item['bytes'];
    }
    echo 'Verified isolated database: all ' . count($counts) . ' application tables empty; identity marker retained.' . PHP_EOL;
    echo 'Verified snapshots: ' . $snapshots . '; cleanup candidates: ' . count($plan) . '; bytes: ' . $bytes . PHP_EOL;
    echo 'Categories: ' . json_encode($categories, JSON_THROW_ON_ERROR) . PHP_EOL;
    if ($mode === '--apply') {
        foreach ($plan as $item) {
            cleanup_require(cleanup_within($item['path'], $item['snapshot']) && cleanup_within($item['snapshot'], $private)
                && !is_link($item['path']) && hash_file('sha256', $item['path']) === $item['sha256'], 'Candidate changed or escaped; stop cleanup.');
            cleanup_require(unlink($item['path']), 'Could not remove owned testing fixture.');
        }
        foreach ($preserved as $path=>$hash) {
            cleanup_require(is_file($path) && hash_file('sha256', $path) === $hash, 'Evidence/protection file changed unexpectedly.');
        }
        foreach ($plan as $item) {
            cleanup_require(!file_exists($item['path']), 'Testing fixture still exists after deletion.');
        }
        cleanup_require(cleanup_original_hashes($project) === $originalHashes, 'Original website files/uploads unexpectedly changed.');
        foreach ($counts as $table=>$count) {
            cleanup_require((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0, 'Testing database no longer empty.');
        }
        $report = ['date'=>'2026-10-07','snapshots'=>$snapshots,'deleted_rows'=>0,'application_table_counts'=>$counts,
            'deleted_files'=>count($plan),'deleted_bytes'=>$bytes,'categories'=>$categories,'files'=>$plan,
            'original_website_hashes_unchanged'=>true,'evidence_and_protection_hashes_unchanged'=>true,'marker_retained'=>true];
        file_put_contents($private . '/cleanup-result.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'PASS: Owned test files deleted, evidence/protection retained, original website untouched, testing database empty.' . PHP_EOL;
    } else {
        echo 'DRY RUN: No files or database rows changed.' . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: Cleanup stopped safely (' . get_class($exception) . '): '
        . ($exception instanceof PDOException ? 'isolated testing database check failed' : $exception->getMessage()) . PHP_EOL);
    exit(1);
}
