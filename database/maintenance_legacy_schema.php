<?php

declare(strict_types=1);

// This maintenance entry point is deliberately restricted to the verified testing instance.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
if (!in_array($argv[1] ?? '', ['--plan', '--apply'], true)) {
    exit("Use --plan, or --apply --confirm-testing-schema. No operation performed.\n");
}
if ($argv[1] === '--apply' && ($argv[2] ?? '') !== '--confirm-testing-schema') {
    fwrite(STDERR, "Explicit testing confirmation required. No operation performed.\n");
    exit(1);
}

require __DIR__ . '/../tests/stage3_database_preflight.php';
try {
    $pdo = stage3_test_database();
    $defaults = require __DIR__ . '/../config/site_data.php';
    $year = $defaults['siteAcademic']['currentYear'] ?? '2025/2026';
    if (!is_string($year) || !preg_match('/\A[0-9]{4}\/[0-9]{4}\z/', $year)) {
        throw new RuntimeException('Invalid maintenance academic year.');
    }
    $operations = [];
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $definitions = [
        'admins' => [
            'full_name' => 'VARCHAR(120) NULL AFTER username',
            'role' => "ENUM('superadmin', 'editor', 'ppdb') NOT NULL DEFAULT 'editor' AFTER full_name",
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER role',
        ],
        'news' => ['is_active' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER published_at'],
    ];
    foreach (['agenda_items', 'school_documents', 'school_achievements'] as $table) {
        $definitions[$table] = ['academic_year' => 'VARCHAR(20) NOT NULL DEFAULT ' . $pdo->quote($year)];
    }
    // Validate the whole plan before any DDL; this is compatibility maintenance, not an installer.
    foreach ($definitions as $table => $columns) {
        if (!in_array($table, $tables, true)) {
            throw new RuntimeException('Required base table missing.');
        }
        $actual = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columns as $column => $definition) {
            if (!in_array($column, $actual, true)) {
                $operations[] = ['sql' => 'ALTER TABLE `' . $table . '` ADD `' . $column . '` ' . $definition, 'params' => null];
            }
        }
        if (isset($columns['academic_year'])) {
            $missing = !in_array('academic_year', $actual, true);
            $count = $missing ? 0 : (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '` WHERE academic_year = "" OR academic_year IS NULL')->fetchColumn();
            if ($count > 0) {
                $operations[] = ['sql' => 'UPDATE `' . $table . '` SET academic_year = :year WHERE academic_year = "" OR academic_year IS NULL', 'params' => ['year' => $year]];
            }
        }
    }
    if (!in_array('admin_activity_logs', $tables, true)) {
        $operations[] = ['sql' => 'CREATE TABLE admin_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, username VARCHAR(80) NULL,
            role VARCHAR(40) NULL, action VARCHAR(80) NOT NULL, module VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NULL, entity_id INT NULL, description TEXT NOT NULL,
            metadata LONGTEXT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_activity_created_at (created_at), INDEX idx_admin_activity_action (action),
            INDEX idx_admin_activity_module (module), INDEX idx_admin_activity_username (username)
        ) ENGINE=InnoDB', 'params' => null];
    }
    echo "Verified isolated testing database only. Operations: " . count($operations) . "\n";
    echo "Legacy ADD defaults: role=editor, is_active=1, news is_active=1. Review before apply.\n";
    echo "DDL is not covered by a rollback guarantee; partial failure requires a new plan.\n";
    foreach ($operations as $operation) {
        echo $operation['sql'] . ";\n";
        if ($argv[1] === '--apply') {
            if ($operation['params'] === null) {
                $pdo->exec($operation['sql']);
            } else {
                $pdo->prepare($operation['sql'])->execute($operation['params']);
            }
        }
    }
    echo $argv[1] === '--plan' ? "PLAN ONLY: no mutations.\n" : "Maintenance completed on testing instance.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Maintenance failed safely; verify testing schema and review a new plan. No fallback attempted.\n");
    exit(1);
}
