<?php
require_admin_permission('gallery');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$galleryImageMaxBytes = IMAGE_UPLOAD_MAX_BYTES;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('gallery', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_gallery_item(database_id($_POST['delete_id'])), 'Item galeri berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_gallery_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'gallery', $galleryImageMaxBytes);
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '/public/logo-sekolah.png');
        }

        if (!$error) {
            save_gallery_item([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'image_path' => $imagePath,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Item galeri berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_gallery_row(database_id($_GET['edit']));
}

$rows = admin_gallery_rows();
$pageTitle = 'Kelola Galeri';
render_view('admin/gallery.php', get_defined_vars(), 'admin');
