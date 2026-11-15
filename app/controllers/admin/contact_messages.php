<?php
require_admin_permission('contact_messages');
require_valid_admin_request_ids();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('contact_messages', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_contact_message(database_id($_POST['delete_id'])), 'Pesan berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } elseif (isset($_POST['message_id'], $_POST['status'])) {
        update_contact_message_status(database_id($_POST['message_id']), trim($_POST['status']));
        $message = 'Status pesan berhasil diperbarui.';
    }
}

$rows = admin_contact_messages();
$statusLabels = [
    'baru' => 'Baru',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
];

$pageTitle = 'Pesan Masuk';
render_view('admin/contact_messages.php', get_defined_vars(), 'admin');
