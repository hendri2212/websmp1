<?php
require_admin_permission('staff');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('staff', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_staff_member(database_id($_POST['delete_id'])), 'Data guru/staf berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_staff_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('photo_file', 'staff');
        $photoPath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($photoPath === null) {
            $photoPath = trim($existing['photo_path'] ?? '');
        }

        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$error && ($name === '' || $position === '' || $subject === '')) {
            $error = 'Nama, jabatan, dan mapel/bidang wajib diisi.';
        } elseif (!$error && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email belum valid.';
        }

        if (!$error) {
            save_staff_member([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'name' => $name,
                'position' => $position,
                'subject' => $subject,
                'bio' => $bio,
                'phone' => trim($_POST['phone'] ?? '') ?: null,
                'email' => $email ?: null,
                'photo_path' => $photoPath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data guru/staf berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_staff_row(database_id($_GET['edit']));
}

$rows = admin_staff_rows();
$pageTitle = 'Kelola Guru & Staf';
render_view('admin/staff.php', get_defined_vars(), 'admin');
