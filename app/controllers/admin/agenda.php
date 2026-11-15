<?php
require_admin_permission('agenda');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('agenda', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_agenda_item(database_id($_POST['delete_id'])), 'Agenda berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startsAt = trim($_POST['starts_at'] ?? '');
        $endsAt = trim($_POST['ends_at'] ?? '');

        if ($title === '' || $category === '' || $location === '' || $description === '' || $startsAt === '') {
            $error = 'Judul, kategori, lokasi, deskripsi, dan tanggal mulai wajib diisi.';
        } else {
            save_agenda_item([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => $title,
                'category' => $category,
                'location' => $location,
                'description' => $description,
                'starts_at' => input_datetime_value($startsAt),
                'ends_at' => $endsAt !== '' ? input_datetime_value($endsAt) : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Agenda berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_agenda_row(database_id($_GET['edit']));
}

$rows = admin_agenda_rows();
$pageTitle = 'Kelola Agenda';
render_view('admin/agenda.php', get_defined_vars(), 'admin');
