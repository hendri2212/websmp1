<?php
require_admin_permission('achievements');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Akademik', 'Non-Akademik', 'Olahraga', 'Seni', 'Karya Ilmiah', 'Sekolah'];
$defaultLevels = ['Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional'];
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('achievements', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_achievement(database_id($_POST['delete_id'])), 'Data prestasi berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_achievement_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'achievements');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '');
        }

        $title = trim($_POST['title'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $achieverName = trim($_POST['achiever_name'] ?? '');
        $achievementDate = trim($_POST['achievement_date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($title === '' || $level === '' || $category === '' || $achieverName === '' || $achievementDate === '' || $description === '')) {
            $error = 'Judul, tingkat, kategori, peraih, tanggal, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_achievement([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => $title,
                'level' => $level,
                'category' => $category,
                'achiever_name' => $achieverName,
                'achievement_date' => $achievementDate,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data prestasi berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_achievement_row(database_id($_GET['edit']));
}

$rows = admin_achievement_rows();
$pageTitle = 'Kelola Prestasi';
render_view('admin/achievements.php', get_defined_vars(), 'admin');
