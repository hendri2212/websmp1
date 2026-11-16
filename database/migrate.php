<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
$arguments = array_slice($argv, 1);
if ($arguments !== [] && $arguments !== ['--fresh']) {
    fwrite(STDERR, "Use: php database/migrate.php [--fresh]\n");
    exit(1);
}

require_once __DIR__ . '/../app/core/database.php';

try {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException('Schema unavailable');
    }
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database unavailable');
    }

    if ($arguments === ['--fresh']) {
        $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $foreignKeyChecks = (int) $pdo->query('SELECT @@FOREIGN_KEY_CHECKS')->fetchColumn();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as [$name, $type]) {
                $identifier = '`' . str_replace('`', '``', $name) . '`';
                $pdo->exec(($type === 'VIEW' ? 'DROP VIEW ' : 'DROP TABLE ') . $identifier);
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=' . $foreignKeyChecks);
        }
    }
    $pdo->exec($schema);
    echo $arguments === ['--fresh']
        ? "Migration fresh selesai. Semua tabel dibuat ulang tanpa data.\n"
        : "Migration selesai. Tabel yang belum ada berhasil dibuat.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration gagal. Periksa .env, koneksi database, dan database/schema.sql.\n");
    exit(1);
}
