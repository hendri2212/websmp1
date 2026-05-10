<?php
$id = (int) ($_GET['id'] ?? 0);
$document = $id ? public_document_row($id) : null;

if (!$document) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$path = realpath(__DIR__ . '/../' . $document['file_path']);
$storageRoot = realpath(__DIR__ . '/../storage/uploads/documents');
if (!$path || !$storageRoot || !str_starts_with($path, $storageRoot) || !is_file($path)) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

increment_document_download_count((int) $document['id']);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . safe_download_filename($document['original_filename'], 'dokumen.pdf') . '"');
readfile($path);
exit;
