<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('db_backup');

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

function backup_relative_path(string $absolutePath, string $rootPath): string
{
    $relative = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($rootPath))), '/');
    return $relative;
}

function backup_add_directory_to_zip(ZipArchive $zip, string $directory, string $rootPath, string $zipPrefix): int
{
    if (!is_dir($directory)) {
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = backup_relative_path($absolutePath, $rootPath);
        $zipPath = trim($zipPrefix . '/' . $relativePath, '/');
        if ($zip->addFile($absolutePath, $zipPath)) {
            $count++;
        }
    }

    return $count;
}

function build_full_backup_zip(PDO $pdo, string $databaseName, array $tableSummaries, string $filename): array
{
    if (!class_exists('ZipArchive')) {
        return ['path' => null, 'error' => 'Ekstensi PHP zip belum aktif, sehingga paket ZIP belum bisa dibuat.', 'files' => 0];
    }

    $projectRoot = realpath(__DIR__ . '/..');
    if (!$projectRoot) {
        return ['path' => null, 'error' => 'Folder project tidak bisa dibaca.', 'files' => 0];
    }

    $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
    $zip = new ZipArchive();
    if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['path' => null, 'error' => 'File ZIP sementara tidak bisa dibuat.', 'files' => 0];
    }

    $sql = build_database_backup($pdo, $databaseName);
    $zip->addFromString('database/backup-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $databaseName) . '.sql', $sql);
    $zip->addFromString('database/schema.sql', file_exists($projectRoot . '/database/schema.sql') ? file_get_contents($projectRoot . '/database/schema.sql') : '');

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
        'restore_note' => 'Import file SQL ke database, lalu salin folder files/public/uploads dan files/storage/uploads ke project website.',
    ];
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $fileCount++;

    $zip->close();

    return ['path' => $tempPath, 'error' => null, 'files' => $fileCount];
}

$config = require __DIR__ . '/../config/database.php';
$pdo = db();
$error = '';
$tableSummaries = [];

if ($pdo) {
    foreach (backup_table_names($pdo) as $table) {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . backup_quote_identifier($table))->fetchColumn();
        $tableSummaries[] = ['table' => $table, 'count' => $count];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$pdo) {
        $error = 'Database belum terhubung sehingga backup tidak bisa dibuat.';
    } else {
        $backupType = $_POST['backup_type'] ?? 'sql';
        if ($backupType === 'full') {
            $filename = 'backup-lengkap-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['database']) . '-' . date('Ymd-His') . '.zip';
            $zipResult = build_full_backup_zip($pdo, $config['database'], $tableSummaries, $filename);
            if ($zipResult['error']) {
                $error = $zipResult['error'];
            } else {
                log_admin_activity('export', 'db_backup', 'Backup lengkap ZIP diunduh.', 'database', null, [
                    'database' => $config['database'],
                    'filename' => $filename,
                    'tables' => count($tableSummaries),
                    'files' => $zipResult['files'],
                ]);

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($zipResult['path']));
                readfile($zipResult['path']);
                unlink($zipResult['path']);
                exit;
            }
        } else {
            $sql = build_database_backup($pdo, $config['database']);
            $filename = 'backup-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['database']) . '-' . date('Ymd-His') . '.sql';
            log_admin_activity('export', 'db_backup', 'Backup database diunduh.', 'database', null, [
                'database' => $config['database'],
                'filename' => $filename,
                'tables' => count($tableSummaries),
            ]);

            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sql));
            echo $sql;
            exit;
        }
    }
}

$pageTitle = 'Backup Database';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Backup Database</h1>
    <p>Unduh salinan database atau paket lengkap berisi database dan file upload. Simpan file backup di tempat aman sebelum update besar, migrasi hosting, atau publikasi website.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!$pdo): ?><div class="alert warning">Database belum terhubung. Jalankan MySQL dan cek konfigurasi database terlebih dahulu.</div><?php endif; ?>
    <?php if (!class_exists('ZipArchive')): ?><div class="alert warning">Ekstensi PHP zip belum aktif. Backup SQL tetap bisa digunakan, tetapi paket backup lengkap ZIP belum tersedia.</div><?php endif; ?>
    <form method="post" class="action-bar">
        <?= csrf_field(); ?>
        <button class="button primary blue" name="backup_type" value="sql" type="submit" <?= $pdo ? '' : 'disabled'; ?>>Unduh Backup SQL</button>
        <button class="button secondary" name="backup_type" value="full" type="submit" <?= $pdo && class_exists('ZipArchive') ? '' : 'disabled'; ?>>Unduh Paket Lengkap ZIP</button>
    </form>
    <p class="muted">Paket lengkap ZIP berisi file SQL, schema, folder <code>public/uploads</code>, folder <code>storage/uploads</code>, dan <code>manifest.json</code>.</p>
</div>

<div class="admin-card">
    <h2>Ringkasan Tabel</h2>
    <?php if (!$tableSummaries): ?>
        <p class="muted">Belum ada tabel yang bisa dibaca.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nama Tabel</th><th>Jumlah Data</th></tr></thead>
                <tbody>
                <?php foreach ($tableSummaries as $summary): ?>
                    <tr>
                        <td><?= e($summary['table']); ?></td>
                        <td><?= number_format((int) $summary['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
