<?php

declare(strict_types=1);

$testKey = (string) getenv('STAGE6_ERROR_TEST_KEY');
if (PHP_SAPI !== 'cli-server' || $testKey === ''
    || !hash_equals($testKey, (string) ($_SERVER['HTTP_X_TEST_KEY'] ?? ''))
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

$scenario = $_GET['scenario'] ?? '';
$allowed = ['warning', 'native-warning', 'pdo', 'runtime', 'error', 'fatal', 'parse-after', 'parse-before'];
if (!is_string($scenario) || !in_array($scenario, $allowed, true)) {
    http_response_code(404);
    exit;
}

// Exercise an included parse failure before application handlers are installed.
if ($scenario === 'parse-before') {
    require getenv('STAGE6_ERROR_BROKEN_FILE');
    exit;
}

require __DIR__ . '/../../includes/error_handling.php';
configure_web_error_handling();
require __DIR__ . '/../../includes/functions.php';
send_security_headers();
$details = 'STAGE6_PRIVATE_CREDENTIAL mysql:host=STAGE6_PRIVATE_HOST;dbname=STAGE6_PRIVATE_DATABASE '
    . "SELECT password_hash FROM admins WHERE username='STAGE6_PRIVATE_USER' "
    . 'C:\\STAGE6_PRIVATE\\config\\database.php memory_limit=STAGE6_PRIVATE_CONFIG';

if ($scenario === 'warning' || $scenario === 'native-warning') {
    if ($scenario === 'warning') {
        trigger_error($details, E_USER_WARNING);
    } else {
        $empty = [];
        $unused = $empty[$details];
    }
    header('Content-Type: application/json');
    echo json_encode([
        'display_errors' => ini_get('display_errors'),
        'display_startup_errors' => ini_get('display_startup_errors'),
        'log_errors' => ini_get('log_errors'),
    ]);
    exit;
}

ob_start();
echo $details;
match ($scenario) {
    'pdo' => throw new PDOException($details),
    'runtime' => throw new RuntimeException($details),
    'error' => throw new Error($details),
    'fatal' => trigger_error($details, E_USER_ERROR),
    'parse-after' => eval('invalid php STAGE6_PRIVATE_PARSE'),
};
