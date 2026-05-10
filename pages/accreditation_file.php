<?php
$accreditation = site_content()['schoolAccreditation'] ?? [];
$filePath = trim((string) ($accreditation['filePath'] ?? ''));

if ($filePath === '') {
    http_response_code(404);
    exit('Lembar akreditasi belum tersedia.');
}

$path = realpath(__DIR__ . '/../' . ltrim($filePath, '/'));
$storageRoot = realpath(__DIR__ . '/../storage/uploads/accreditation');
if (!$path || !$storageRoot || !str_starts_with($path, $storageRoot) || !is_file($path)) {
    http_response_code(404);
    exit('Lembar akreditasi tidak ditemukan.');
}

$filename = $accreditation['originalFilename'] ?? 'lembar-akreditasi.pdf';

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . safe_download_filename($filename, 'lembar-akreditasi.pdf') . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
