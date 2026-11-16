<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
if (count($argv) !== 2 || $argv[1] !== '--initialize') {
    fwrite(STDERR, "Use: php database/initialize_admin.php --initialize\n");
    exit(1);
}

require_once __DIR__ . '/../app/core/database.php';

try {
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database unavailable');
    }
    if ($pdo->query('SELECT id FROM admins LIMIT 1')->fetchColumn() !== false) {
        fwrite(STDERR, "Tabel admins sudah berisi akun. Tidak ada perubahan.\n");
        exit(1);
    }

    $statement = $pdo->prepare("INSERT INTO admins (username, full_name, role, is_active, password_hash) VALUES (?, ?, 'superadmin', 1, ?)");
    $statement->execute(['admin', 'Administrator', password_hash('12345', PASSWORD_DEFAULT)]);
    echo "Akun admin berhasil dibuat.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Inisialisasi gagal. Periksa koneksi database dan struktur tabel admins.\n");
    exit(1);
}
