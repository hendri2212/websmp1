<?php
require_admin_permission('admins');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$roleOptions = admin_role_options();
$roleDescriptions = admin_role_descriptions();
$rolePermissions = admin_permissions();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('admins', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_admin_user(database_id($_POST['delete_id'])), 'Akun admin berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $role === '') {
            $error = 'Username dan role wajib diisi.';
        } elseif (empty($_POST['id']) && $password === '') {
            $error = 'Password wajib diisi untuk akun baru.';
        }

        if (!$error) {
            $result = save_admin_user([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'username' => $username,
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => $role,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'password' => $password,
            ]);
            $message = $result['success'] ? $result['message'] : '';
            $error = $result['success'] ? '' : $result['message'];
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_user(database_id($_GET['edit']));
}

$rows = admin_users();
$pageTitle = 'Kelola Akun Admin';
render_view('admin/admins.php', get_defined_vars(), 'admin');
