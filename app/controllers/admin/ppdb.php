<?php
require_admin_permission('ppdb_content');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

$sectionLabels = [
    'informasi' => 'Informasi Umum', 'kriteria' => 'Jalur Pendaftaran',
    'alur' => 'Alur Pendaftaran', 'persyaratan' => 'Persyaratan',
    'catatan' => 'Catatan Penting', 'kontak' => 'Kontak Panitia', 'pengantar' => 'Teks Pengantar',
];
$introductionLabels = [
    'halaman' => 'Pengantar Halaman PPDB', 'kriteria' => 'Pengantar Jalur Pendaftaran',
    'dokumen' => 'Pengantar Dokumen', 'kontak' => 'Pengantar Kontak Panitia',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (($_POST['section'] ?? '') === 'pengantar') {
        $_POST['title'] = $_POST['introduction_slot'] ?? '';
    }
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($_POST['section'] ?? '') === 'pengantar' && !isset($introductionLabels[$_POST['title']])) {
        $error = 'Pilih posisi teks pengantar yang tersedia.';
    } elseif (($validationError = input_validation_error('ppdb_content', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_ppdb_entry(database_id($_POST['delete_id'])), 'Informasi PPDB berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        save_ppdb_entry([
            'id' => ($_POST['id'] ?? '') !== '' ? database_id($_POST['id']) : null,
            'section' => trim($_POST['section']),
            'title' => trim($_POST['title']),
            'content' => trim($_POST['content']),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        $message = 'Informasi PPDB berhasil disimpan.';
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_ppdb_row(database_id($_GET['edit']));
}

$rows = ppdb_entries();

foreach ($rows as $row) {
    $sectionLabels[$row['section']] ??= $row['section'];
}

$pageTitle = 'Kelola Informasi PPDB';
render_view('admin/ppdb.php', get_defined_vars(), 'admin');
