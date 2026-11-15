<?php
require_admin_permission('activity_logs');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'module' => trim($_GET['module'] ?? ''),
    'action' => trim($_GET['action'] ?? ''),
];
$logs = admin_activity_logs($filters);
$modules = admin_activity_log_options('module');
$actions = admin_activity_log_options('action');

$pageTitle = 'Log Aktivitas Admin';
render_view('admin/activity_logs.php', get_defined_vars(), 'admin');
