<?php

if (PHP_SAPI !== 'cli-server') { http_response_code(403); exit; }
$manifest = json_decode(file_get_contents(__DIR__ . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (!hash_equals($manifest['key'], $_SERVER['HTTP_X_TEST_KEY'] ?? '')) { http_response_code(403); exit; }
define('STAGE5_FAILURE_TEST', true);
$GLOBALS['stage5_scenario'] = $_SERVER['HTTP_X_TEST_SCENARIO'] ?? 'healthy';
require __DIR__ . '/failure-database.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/__session') {
    require __DIR__ . '/includes/bootstrap.php';
    header('Content-Type: application/json');
    echo json_encode(['authenticated'=>isset($_SESSION['admin_logged_in']), 'identity'=>isset($_SESSION['admin_id']), 'role'=>isset($_SESSION['admin_role'])]);
} elseif (in_array($path, ['/admin/login.php', '/admin/news.php', '/admin/logout.php'], true)) {
    require __DIR__ . $path;
} elseif (in_array($path, ['/', '/berita', '/profil'], true)) {
    require __DIR__ . '/index.php';
} else {
    http_response_code(403);
}
