<?php

// The PHP development server does not enforce Apache .htaccess rules.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once __DIR__ . '/app/core/error_handling.php';
configure_web_error_handling();
require_once __DIR__ . '/app/core/functions.php';

$previewPath = str_replace('\\', '/', rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
if ($previewPath !== '/pages/ppdb_receipt.php' && (preg_match('~(^|/)\.[^/]*|^/(app|config|includes|storage|database|tests|templates|pages)(/|$)|(^|/)(backup|backups|source_docs)(/|$)|\.(sql|md|env|ini|log|bak|backup)$~i', $previewPath)
    || $previewPath === '/preview_router.php')) {
    http_response_code(403);
    exit('Akses ditolak.');
}

if (str_starts_with($previewPath, '/public/')) {
    $previewFile = __DIR__ . $previewPath;
    if (preg_match('~\.(php|phtml|phar|cgi|pl|asp|aspx|jsp)(/|$)~i', $previewPath)
        || !file_is_within_directory($previewFile, __DIR__ . '/public')) {
        http_response_code(404);
        exit('Berkas tidak ditemukan.');
    }
    return false;
}

require __DIR__ . '/index.php';
