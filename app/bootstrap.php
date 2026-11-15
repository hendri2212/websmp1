<?php

require_once __DIR__ . '/core/paths.php';

require_once APP_ROOT . '/app/core/error_handling.php';
if (PHP_SAPI !== 'cli') {
    configure_web_error_handling();
}
require_once APP_ROOT . '/app/core/functions.php';

if (PHP_SAPI !== 'cli') {
    require_http_methods(['GET', 'HEAD', 'POST']);
}

// Reject incomplete configuration before sessions or database access, including login.
try {
    app_config();
    require APP_ROOT . '/config/database.php';
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
    $localSessionPath = APP_ROOT . '/storage/sessions';
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

require_once APP_ROOT . '/app/core/database.php';
require_once APP_ROOT . '/app/models/admin.php';
require_once APP_ROOT . '/app/core/view.php';
require_once APP_ROOT . '/app/models/settings.php';
require_once APP_ROOT . '/app/models/accounts.php';
require_once APP_ROOT . '/app/models/news.php';
require_once APP_ROOT . '/app/models/gallery.php';
require_once APP_ROOT . '/app/models/announcements.php';
require_once APP_ROOT . '/app/models/staff.php';
require_once APP_ROOT . '/app/models/agenda.php';
require_once APP_ROOT . '/app/models/documents.php';
require_once APP_ROOT . '/app/models/achievements.php';
require_once APP_ROOT . '/app/models/extracurriculars.php';
require_once APP_ROOT . '/app/models/facilities.php';
require_once APP_ROOT . '/app/models/faqs.php';
require_once APP_ROOT . '/app/models/ppdb.php';
require_once APP_ROOT . '/app/models/contacts.php';
require_once APP_ROOT . '/app/models/search.php';
require_once APP_ROOT . '/app/models/dashboard.php';
require_once APP_ROOT . '/app/models/backup.php';
