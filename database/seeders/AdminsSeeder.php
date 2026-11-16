<?php

declare(strict_types=1);

final class AdminsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        if ($pdo->query('SELECT id FROM admins LIMIT 1')->fetchColumn() !== false) {
            return 0;
        }
        $statement = $pdo->prepare("INSERT INTO admins (username, full_name, role, is_active, password_hash) VALUES (?, ?, 'superadmin', 1, ?)");
        $statement->execute(['admin', 'Administrator', password_hash('12345', PASSWORD_DEFAULT)]);
        return 1;
    }
}
