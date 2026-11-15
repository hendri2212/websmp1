<?php
require_admin_permission('documents');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Kalender Akademik', 'Formulir', 'Surat Edaran', 'Tata Tertib', 'PPDB', 'Lainnya'];
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('documents', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_school_document(database_id($_POST['delete_id'])), 'Dokumen berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_document_row(database_id($_POST['id'])) : null;
        $upload = uploaded_pdf_result('document_file', 'documents');
        $filePath = $upload['path'];
        $originalFilename = $upload['original_filename'];
        $fileSize = $upload['file_size'];

        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($filePath === null && !$existing) {
            $error = 'File PDF wajib diunggah untuk dokumen baru.';
        } elseif ($filePath === null && $existing) {
            $filePath = $existing['file_path'];
            $originalFilename = $existing['original_filename'];
            $fileSize = (int) $existing['file_size'];
        }

        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($title === '' || $category === '' || $description === '')) {
            $error = 'Judul, kategori, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_school_document([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => $title,
                'category' => $category,
                'description' => $description,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Dokumen berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_document_row(database_id($_GET['edit']));
}

$rows = admin_document_rows();
$pageTitle = 'Kelola Dokumen';
render_view('admin/documents.php', get_defined_vars(), 'admin');
