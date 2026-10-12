<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
$paths = [
    'information'=>'pages/ppdb.php',
    'receipt'=>'pages/ppdb_receipt.php',
    'applications'=>'admin/ppdb_applications.php',
    'export'=>'admin/ppdb_export.php',
    'download'=>'admin/ppdb_download.php',
];
$key = $argv[1] ?? '';
$method = $argv[2] ?? '';
if (!isset($paths[$key]) || !in_array($method, ['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'], true)) {
    exit(1);
}
$_SERVER['REQUEST_METHOD'] = $method;
$_GET = ['action'=>'register', 'role'=>'superadmin', 'registration_number'=>'TEST'];
$_POST = ['action'=>'delete', 'application_id'=>'1', 'role'=>'superadmin'];
http_response_code(200);
ob_start();
register_shutdown_function(static function (): void {
    $body = ob_get_clean();
    echo json_encode(['status'=>http_response_code(), 'body'=>$body], JSON_THROW_ON_ERROR);
});
require dirname(__DIR__, 2) . '/' . $paths[$key];
