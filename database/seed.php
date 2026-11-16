<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$seeders = [
    'DatabaseSeeder',
    'SiteSettingsSeeder',
    'SchoolFacilitiesSeeder',
    'ExtracurricularsSeeder',
    'PpdbEntriesSeeder',
    'AgendaItemsSeeder',
    'AnnouncementsSeeder',
    'FaqsSeeder',
    'AdminsSeeder',
];
$arguments = array_slice($argv, 1);
if ($arguments === ['--list']) {
    echo implode(PHP_EOL, $seeders) . PHP_EOL;
    exit;
}
$class = 'DatabaseSeeder';
if (count($arguments) === 2 && str_starts_with($arguments[1], '--class=')) {
    $class = substr($arguments[1], strlen('--class='));
}
if (($arguments[0] ?? '') !== '--seed' || count($arguments) > 2
    || (count($arguments) === 2 && !str_starts_with($arguments[1], '--class='))
    || !in_array($class, $seeders, true)) {
    fwrite(STDERR, "Use: php database/seed.php --seed [--class=SeederName] or --list\n");
    exit(1);
}

require_once __DIR__ . '/../app/core/database.php';
require_once __DIR__ . '/seeders/Seeder.php';
foreach ($seeders as $name) {
    require_once __DIR__ . '/seeders/' . $name . '.php';
}

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "Database belum terhubung. Periksa .env.\n");
    exit(1);
}
try {
    $pdo->beginTransaction();
    $changed = (new $class())->run($pdo);
    $pdo->commit();
    echo "$class selesai: $changed data ditambahkan atau dilengkapi.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed gagal. Periksa koneksi dan struktur database; perubahan dibatalkan.\n");
    exit(1);
}
