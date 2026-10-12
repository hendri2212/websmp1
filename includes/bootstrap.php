<?php

require_once __DIR__ . '/error_handling.php';
if (PHP_SAPI !== 'cli') {
    configure_web_error_handling();
}
require_once __DIR__ . '/functions.php';

if (PHP_SAPI !== 'cli') {
    require_http_methods(['GET', 'HEAD', 'POST']);
}

// Reject incomplete configuration before sessions or database access, including login.
try {
    app_config();
    require __DIR__ . '/../config/database.php';
} catch (Throwable $exception) {
    if (PHP_SAPI === 'cli') {
        throw $exception;
    }
    send_generic_error_response(503);
    exit;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

if (PHP_SAPI !== 'cli' && app_config()['environment'] === 'local' && ini_get('session.save_handler') === 'files') {
    $localSessionPath = __DIR__ . '/../storage/sessions';
    if (is_dir($localSessionPath) || mkdir($localSessionPath, 0770, true)) {
        session_save_path($localSessionPath);
    }
}

$isSecure = request_is_https();

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
send_security_headers();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/repositories.php';
