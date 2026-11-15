<?php

function backup_table_names(PDO $pdo): array
{
    $rows = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    return array_map(static fn(array $row): string => $row[0], $rows);
}

function backup_quote_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function backup_sql_value(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return $pdo->quote((string) $value);
}

function build_database_backup(PDO $pdo, string $databaseName): string
{
    $lines = [
        '-- Backup database website sekolah',
        '-- Database: ' . $databaseName,
        '-- Dibuat: ' . date('Y-m-d H:i:s'),
        '',
        'SET FOREIGN_KEY_CHECKS=0;',
        'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
        '',
    ];

    foreach (backup_table_names($pdo) as $table) {
        $quotedTable = backup_quote_identifier($table);
        $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';

        $lines[] = '--';
        $lines[] = '-- Struktur tabel ' . $quotedTable;
        $lines[] = '--';
        $lines[] = 'DROP TABLE IF EXISTS ' . $quotedTable . ';';
        $lines[] = $createSql . ';';
        $lines[] = '';

        $rows = $pdo->query('SELECT * FROM ' . $quotedTable)->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            continue;
        }

        $columns = array_map('backup_quote_identifier', array_keys($rows[0]));
        $lines[] = '--';
        $lines[] = '-- Data tabel ' . $quotedTable;
        $lines[] = '--';

        foreach ($rows as $row) {
            $values = array_map(static fn(mixed $value): string => backup_sql_value($pdo, $value), array_values($row));
            $lines[] = 'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
        }
        $lines[] = '';
    }

    $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
    $lines[] = '';

    return implode("\n", $lines);
}

function backup_path_within(string $path, string $root): bool
{
    $path = realpath($path);
    $root = realpath($root);
    if ($path === false || $root === false) { return false; }
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    if (PHP_OS_FAMILY === 'Windows') { $path = strtolower($path); $root = strtolower($root); }
    return str_starts_with($path, $root);
}

function backup_relative_path(string $absolutePath, string $rootPath): string
{
    if (!file_is_within_directory($absolutePath, $rootPath) || is_link($absolutePath)) {
        throw new RuntimeException('File backup tidak dapat diverifikasi.');
    }
    $relative = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($rootPath))), '/');
    return $relative;
}

function backup_add_directory_to_zip(ZipArchive $zip, string $directory, string $rootPath, string $zipPrefix): int
{
    if (!is_dir($directory)) {
        return 0;
    }
    if (is_link($directory) || !backup_path_within($directory, $rootPath)) {
        throw new RuntimeException('Direktori backup tidak dapat diverifikasi.');
    }

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isLink() || !backup_path_within($fileInfo->getPathname(), $directory)) {
            throw new RuntimeException('File backup tidak dapat diverifikasi.');
        }
        if (!$fileInfo->isFile()) {
            continue;
        }
        if (preg_match('/\A(?:\.env(?:\..*)?|\.htpasswd|(?:database|config|credentials|connection)\.(?:php|ini|json)|.*\.(?:ini|cnf))\z/i', $fileInfo->getFilename())) {
            throw new RuntimeException('File konfigurasi tidak boleh disertakan dalam backup upload.');
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = backup_relative_path($absolutePath, $rootPath);
        $zipPath = trim($zipPrefix . '/' . $relativePath, '/');
        if (!$zip->addFile($absolutePath, $zipPath)) {
            throw new RuntimeException('File backup gagal ditambahkan.');
        }
        $count++;
    }

    return $count;
}

function build_full_backup_zip(PDO $pdo, string $databaseName, array $tableSummaries, string $filename): array
{
    if (!preg_match('/\Abackup-lengkap-[a-zA-Z0-9_-]+-[0-9]{8}-[0-9]{6}\.zip\z/', $filename)) {
        return ['path'=>null, 'error'=>'Nama backup tidak valid.', 'files'=>0];
    }
    if (!class_exists('ZipArchive')) {
        return ['path' => null, 'error' => 'Ekstensi PHP zip belum aktif, sehingga paket ZIP belum bisa dibuat.', 'files' => 0];
    }

    $projectRoot = APP_ROOT;
    if (!$projectRoot) {
        return ['path' => null, 'error' => 'Folder project tidak bisa dibaca.', 'files' => 0];
    }

    $privateDirectory = $projectRoot;
    foreach (['storage', 'backups', '.temporary'] as $segment) {
        $privateDirectory .= DIRECTORY_SEPARATOR . $segment;
        if (is_link($privateDirectory) || (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0700))
            || !backup_path_within($privateDirectory, $projectRoot)) {
            throw new RuntimeException('Penyimpanan backup tidak tersedia.');
        }
    }
    $tempPath = $privateDirectory . DIRECTORY_SEPARATOR . 'backup-' . bin2hex(random_bytes(24)) . '.zip';
    $reservation = fopen($tempPath, 'x+b');
    if (!$reservation) {
        throw new RuntimeException('Penyimpanan backup tidak tersedia.');
    }
    fclose($reservation);
    chmod($tempPath, 0600);
    register_shutdown_function(static function () use ($tempPath): void {
        if (is_file($tempPath) && !is_link($tempPath) && !@unlink($tempPath)) {
            error_log('Temporary backup cleanup deferred; private storage requires review.');
        }
    });
    $zip = new ZipArchive();
    try {
    if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('File ZIP sementara tidak bisa dibuat.');
    }

    $sql = build_database_backup($pdo, $databaseName);
    if (!$zip->addFromString('database/backup-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $databaseName) . '.sql', $sql)) {
        throw new RuntimeException('Isi backup gagal ditulis.');
    }
    // Installation schema is optional in isolated fixtures, but read failures must not silently produce an empty entry.
    $schemaPath = $projectRoot . '/database/schema.sql';
    if (is_link($schemaPath) || (is_file($schemaPath) && !file_is_within_directory($schemaPath, $projectRoot . '/database'))) {
        throw new RuntimeException('Schema backup tidak dapat diverifikasi.');
    }
    $schema = is_file($schemaPath) ? file_get_contents($schemaPath) : '';
    if ($schema === false || !$zip->addFromString('database/schema.sql', $schema)) {
        throw new RuntimeException('Isi backup gagal ditulis.');
    }

    $fileCount = 2;
    $fileCount += backup_add_directory_to_zip($zip, $projectRoot . '/public/uploads', $projectRoot, 'files');
    $fileCount += backup_add_directory_to_zip($zip, $projectRoot . '/storage/uploads', $projectRoot, 'files');

    $manifest = [
        'website' => 'SMP Negeri 1 Kotabaru',
        'database' => $databaseName,
        'created_at' => date('Y-m-d H:i:s'),
        'tables' => $tableSummaries,
        'included_paths' => [
            'database/backup-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $databaseName) . '.sql',
            'database/schema.sql',
            'files/public/uploads',
            'files/storage/uploads',
        ],
        'restore_note' => 'Gunakan database/backup-*.sql untuk pemulihan data, bukan database/schema.sql (schema instalasi kosong). Review target dan statement SQL sebelum import. Salin files/public/uploads dan files/storage/uploads secara terkendali.',
    ];
    if (!$zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
        throw new RuntimeException('Isi backup gagal ditulis.');
    }
    $fileCount++;

    if (!$zip->close()) {
        throw new RuntimeException('File ZIP gagal diselesaikan.');
    }

    return ['path' => $tempPath, 'error' => null, 'files' => $fileCount];
    } catch (Throwable $exception) {
        error_log('Backup generation failed (' . get_class($exception) . ').');
        try { $zip->close(); } catch (Throwable $closeException) {}
        if (is_file($tempPath) && !is_link($tempPath)) { @unlink($tempPath); }
        throw new RuntimeException('Backup gagal dibuat. Silakan coba lagi.');
    }
}

function backup_table_summaries(PDO $pdo): array
{
    $summaries = [];
    foreach (backup_table_names($pdo) as $table) {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . backup_quote_identifier($table))->fetchColumn();
        $summaries[] = ['table' => $table, 'count' => $count];
    }
    return $summaries;
}
