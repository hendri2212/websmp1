<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('ppdb_applications');

$id = (int) ($_GET['id'] ?? 0);
$file = $_GET['file'] ?? '';
$fields = [
    'family_card' => 'family_card_path',
    'birth_certificate' => 'birth_certificate_path',
    'report_card' => 'report_card_path',
    'photo' => 'photo_path',
];

if (!$id || !isset($fields[$file])) {
    http_response_code(404);
    exit('Berkas tidak ditemukan.');
}

$row = admin_ppdb_application($id);
if (!$row || empty($row[$fields[$file]])) {
    http_response_code(404);
    exit('Berkas tidak ditemukan.');
}

$path = realpath(__DIR__ . '/../' . $row[$fields[$file]]);
$storageRoot = realpath(__DIR__ . '/../storage/uploads/ppdb');
if (!$path || !$storageRoot || !str_starts_with($path, $storageRoot) || !is_file($path)) {
    http_response_code(404);
    exit('Berkas tidak ditemukan.');
}

$mimeType = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . safe_download_filename(basename($path), 'berkas-ppdb') . '"');
readfile($path);
exit;
