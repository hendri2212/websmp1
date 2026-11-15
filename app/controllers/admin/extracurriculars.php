<?php
require_admin_permission('extracurriculars');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Wajib', 'Olahraga', 'Seni', 'Akademik', 'Organisasi', 'Kesehatan', 'Keagamaan', 'Teknologi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('extracurriculars', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_extracurricular(database_id($_POST['delete_id'])), 'Data ekstrakurikuler berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_extracurricular_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'extracurriculars');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '/public/logo-sekolah.png');
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($name === '' || $category === '' || $description === '')) {
            $error = 'Nama ekskul, kategori, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_extracurricular([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'name' => $name,
                'category' => $category,
                'coach' => trim($_POST['coach'] ?? '') ?: null,
                'schedule_text' => trim($_POST['schedule_text'] ?? '') ?: null,
                'location' => trim($_POST['location'] ?? '') ?: null,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data ekstrakurikuler berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_extracurricular_row(database_id($_GET['edit']));
}

$rows = admin_extracurricular_rows();
$pageTitle = 'Kelola Ekstrakurikuler';
render_view('admin/extracurriculars.php', get_defined_vars(), 'admin');
