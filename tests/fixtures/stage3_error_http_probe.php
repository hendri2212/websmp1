<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server' || !defined('STAGE3_ISOLATED_HTTP') || STAGE3_ISOLATED_HTTP !== true) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$pdo = stage3_test_database();
$scenario = $_GET['scenario'] ?? '';
$sentinel = 'STAGE3_PRIVATE_SENTINEL_PASSWORD | mysql:host=STAGE3_PRIVATE_HOST;dbname=STAGE3_PRIVATE_DATABASE'
    . ' | C:\\STAGE3_PRIVATE_PATH\\config\\database.php | SELECT password_hash FROM admins'
    . ' | memory_limit=STAGE3_PRIVATE_CONFIGURATION';

if ($scenario === 'warning') {
    trigger_error($sentinel, E_USER_WARNING);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'display_errors'=>ini_get('display_errors'),'display_startup_errors'=>ini_get('display_startup_errors')]);
    exit;
}

if ($scenario === 'query') {
    $pdo->query('SELECT STAGE3_COLUMN_DOES_NOT_EXIST FROM admins WHERE 1=0');
} elseif ($scenario === 'buffered-exception') {
    ob_start();
    echo 'Sensitive partial response: ' . $sentinel;
    throw new RuntimeException($sentinel);
} elseif ($scenario === 'fatal') {
    ob_start();
    echo 'Sensitive partial response: ' . $sentinel;
    trigger_error($sentinel, E_USER_ERROR);
} else {
    http_response_code(404);
    exit('Probe tidak ditemukan.');
}
