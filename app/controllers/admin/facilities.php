<?php
require_admin_permission('facilities');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Ruang Belajar', 'Laboratorium', 'Perpustakaan', 'Olahraga', 'Kesehatan', 'Ibadah', 'Administrasi', 'Pendukung'];
$defaultConditions = ['Baik', 'Sangat Baik', 'Perlu Perawatan', 'Dalam Perbaikan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('facilities', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_facility(database_id($_POST['delete_id'])), 'Data fasilitas berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_facility_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'facilities');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '');
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $conditionStatus = trim($_POST['condition_status'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($name === '' || $category === '' || $conditionStatus === '' || $description === '')) {
            $error = 'Nama fasilitas, kategori, kondisi, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_facility([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'name' => $name,
                'category' => $category,
                'location' => trim($_POST['location'] ?? '') ?: null,
                'capacity' => trim($_POST['capacity'] ?? '') ?: null,
                'condition_status' => $conditionStatus,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data fasilitas berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_facility_row(database_id($_GET['edit']));
}

$rows = admin_facility_rows();
$pageTitle = 'Kelola Fasilitas';
render_view('admin/facilities.php', get_defined_vars(), 'admin');
