<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/stage3_database_preflight.php';
$pdo = stage3_test_database();
$tables = array_merge(array_keys(require __DIR__ . '/fixtures/stage3_content_tables.php'), ['admins','site_settings','admin_activity_logs','ppdb_applications','ppdb_entries']);
foreach ($tables as $table) {
    if ((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() !== 0) {
        throw new RuntimeException('Nonempty isolated fixture; stop before changing test schema.');
    }
}
$columns = $pdo->query('SHOW COLUMNS FROM ppdb_applications')->fetchAll(PDO::FETCH_COLUMN);
foreach (['family_card_path','birth_certificate_path','report_card_path','photo_path'] as $column) {
    if (!in_array($column, $columns, true)) {
        $pdo->exec('ALTER TABLE ppdb_applications ADD COLUMN `' . $column . '` VARCHAR(255) NULL');
    }
}
echo "PASS: Empty isolated fixture includes four existing application file columns; no applicant inserted.\n";
