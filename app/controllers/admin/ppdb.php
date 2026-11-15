<?php
require_admin_permission('ppdb_content');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
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

$pageTitle = 'Kelola Informasi PPDB';
render_view('admin/ppdb.php', get_defined_vars(), 'admin');
