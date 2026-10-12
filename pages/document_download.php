<?php
$id = database_id($_GET['id'] ?? null);
$document = $id ? public_document_row($id) : null;

if (!$document) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$path = realpath(__DIR__ . '/../' . $document['file_path']);
$storageRoot = realpath(__DIR__ . '/../storage/uploads/documents');
if (!$path || !$storageRoot || !file_is_within_directory($path, $storageRoot)) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . safe_download_filename($document['original_filename'], 'dokumen.pdf') . '"');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}
increment_document_download_count((int) $document['id']);
readfile($path);
exit;
