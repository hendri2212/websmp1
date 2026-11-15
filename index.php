<?php

require_once __DIR__ . '/app/core/paths.php';
require_once APP_ROOT . '/app/core/error_handling.php';
configure_web_error_handling();
require_once APP_ROOT . '/app/core/functions.php';

$routes = require APP_ROOT . '/config/routes.php';
$controller = $routes[current_path()] ?? 'site.php';

// Retired applicant endpoints must respond without sessions or database access.
$retired = ['files/ppdb_receipt.php', 'admin/ppdb_applications.php', 'admin/ppdb_download.php', 'admin/ppdb_export.php'];
if (!in_array($controller, $retired, true)) {
    require APP_ROOT . '/app/bootstrap.php';
}

require APP_ROOT . '/app/controllers/' . $controller;
