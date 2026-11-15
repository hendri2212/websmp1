<?php
require_admin_permission('change_password');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('change_password', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru belum sama.';
    } else {
        $result = update_admin_password(admin_username() ?? '', $currentPassword, $newPassword);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Ganti Password Admin';
render_view('admin/change_password.php', get_defined_vars(), 'admin');
