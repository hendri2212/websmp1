<?php
require_admin_permission('faqs');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Umum', 'PPDB', 'Akademik', 'Administrasi', 'Kesiswaan', 'Dokumen', 'Kontak'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('faqs', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_faq(database_id($_POST['delete_id'])), 'FAQ berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if ($question === '' || $answer === '' || $category === '') {
            $error = 'Pertanyaan, jawaban, dan kategori wajib diisi.';
        }

        if (!$error) {
            save_faq([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'FAQ berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_faq_row(database_id($_GET['edit']));
}

$rows = admin_faq_rows();
$pageTitle = 'Kelola FAQ';
render_view('admin/faqs.php', get_defined_vars(), 'admin');
