<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('ppdb_applications');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'academic_year' => trim($_GET['academic_year'] ?? current_academic_year()),
];
$rows = admin_ppdb_applications($filters);
$filename = 'pendaftar-ppdb-' . date('Ymd-His') . '.csv';
log_admin_activity('export', 'ppdb_applications', 'Data pendaftar PPDB diexport ke CSV.', 'ppdb_application', null, [
    'filename' => $filename,
    'row_count' => count($rows),
    'filters' => $filters,
]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [
    'Nomor Pendaftaran',
    'Tahun Ajaran',
    'Tanggal Daftar',
    'Status',
    'Nama Calon Siswa',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Jenis Kelamin',
    'NISN',
    'Asal Sekolah',
    'Jalur Pendaftaran',
    'Nama Orang Tua/Wali',
    'No. HP Orang Tua/Wali',
    'Email Orang Tua/Wali',
    'Alamat',
    'Persetujuan Privasi',
    'Versi Privasi',
    'Catatan Admin',
]);

foreach ($rows as $row) {
    fputcsv($output, [
        csv_safe_cell($row['registration_number']),
        csv_safe_cell($row['academic_year'] ?? ''),
        csv_safe_cell(date('Y-m-d H:i:s', strtotime($row['created_at']))),
        csv_safe_cell($row['status']),
        csv_safe_cell($row['student_name']),
        csv_safe_cell($row['birth_place']),
        csv_safe_cell($row['birth_date']),
        csv_safe_cell($row['gender']),
        csv_safe_cell($row['nisn']),
        csv_safe_cell($row['previous_school']),
        csv_safe_cell($row['registration_path'] ?? ''),
        csv_safe_cell($row['parent_name']),
        csv_safe_cell($row['parent_phone']),
        csv_safe_cell($row['parent_email']),
        csv_safe_cell($row['address']),
        csv_safe_cell($row['consent_accepted_at'] ?? ''),
        csv_safe_cell($row['privacy_version'] ?? ''),
        csv_safe_cell($row['admin_notes'] ?? ''),
    ]);
}

fclose($output);
exit;
