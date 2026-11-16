<?php
require_admin_permission('announcements');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('announcements', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_announcement(database_id($_POST['delete_id'])), 'Pengumuman berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $title = trim($_POST['title'] ?? '');
        $contentText = trim($_POST['content'] ?? '');
        $startsAt = trim($_POST['starts_at'] ?? '');
        $endsAt = trim($_POST['ends_at'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');

        if ($title === '' || $contentText === '') {
            $error = 'Judul dan isi pengumuman wajib diisi.';
        } elseif ($linkUrl !== '' && safe_content_url($linkUrl) === '') {
            $error = 'Tautan pengumuman tidak valid. Periksa alamat yang Anda masukkan.';
        } else {
            save_announcement([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => $title,
                'content' => $contentText,
                'link_label' => trim($_POST['link_label'] ?? '') ?: null,
                'link_url' => $linkUrl !== '' ? $linkUrl : null,
                'starts_at' => $startsAt !== '' ? input_datetime_value($startsAt) : null,
                'ends_at' => $endsAt !== '' ? input_datetime_value($endsAt) : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Pengumuman berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_announcement_row(database_id($_GET['edit']));
}

$rows = admin_announcement_rows();
$pageTitle = 'Kelola Pengumuman';
render_view('admin/announcements.php', get_defined_vars(), 'admin');
