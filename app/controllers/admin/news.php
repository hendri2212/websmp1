<?php
require_admin_permission('news');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('news', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_news(database_id($_POST['delete_id'])), 'Berita berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_news_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'news');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '/public/logo-sekolah.png');
        }

        if (!$error) {
            save_news([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'slug' => trim($_POST['slug']),
                'title' => trim($_POST['title']),
                'category' => trim($_POST['category']),
                'excerpt' => trim($_POST['excerpt']),
                'content' => trim($_POST['content']),
                'image_path' => $imagePath,
                'published_at' => input_datetime_value($_POST['published_at']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            commit_uploaded_files();
            $message = 'Berita berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_news_row(database_id($_GET['edit']));
}

$rows = admin_news_rows();
$pageTitle = 'Kelola Berita';
render_view('admin/news.php', get_defined_vars(), 'admin');
