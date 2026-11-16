<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
if (count($argv) !== 1) {
    fwrite(STDERR, "Use: php database/migrate.php\n");
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

    $pdo->exec($schema);
    echo "Migration selesai. Tabel yang belum ada berhasil dibuat.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration gagal. Periksa .env, koneksi database, dan database/schema.sql.\n");
    exit(1);
}
