<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (PHP_SAPI === 'cli') {
    $sessionPath = __DIR__ . '/.tmp_sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    session_save_path($sessionPath);
}

require_once __DIR__ . '/../includes/bootstrap.php';

$failures = [];
$passes = [];

function smoke_pass(string $message): void
{
    global $passes;
    $passes[] = $message;
    echo '[OK] ' . $message . PHP_EOL;
}

function smoke_fail(string $message): void
{
    global $failures;
    $failures[] = $message;
    echo '[FAIL] ' . $message . PHP_EOL;
}

function smoke_assert(bool $condition, string $message): void
{
    $condition ? smoke_pass($message) : smoke_fail($message);
}

function smoke_id(string $table, string $column, string $value): ?int
{
    $allowed = [
        'news' => ['title', 'slug'],
        'gallery_items' => ['title'],
        'announcements' => ['title'],
        'staff_members' => ['name'],
        'agenda_items' => ['title'],
        'school_documents' => ['title'],
        'school_achievements' => ['title'],
        'extracurriculars' => ['name'],
        'school_facilities' => ['name'],
        'faqs' => ['question'],
        'ppdb_entries' => ['title'],
        'contact_messages' => ['subject'],
        'ppdb_applications' => ['registration_number', 'student_name'],
        'admins' => ['username'],
    ];

    if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
        throw new InvalidArgumentException('Kolom smoke test tidak diizinkan.');
    }

    $statement = db()->prepare(
        'SELECT id FROM ' . repository_quote_identifier($table) .
        ' WHERE ' . repository_quote_identifier($column) . ' = :value ORDER BY id DESC LIMIT 1'
    );
    $statement->execute(['value' => $value]);
    $id = $statement->fetchColumn();

    return $id ? (int) $id : null;
}

function smoke_cleanup(string $marker): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $queries = [
        ['DELETE FROM news WHERE title LIKE :marker OR slug LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM gallery_items WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM announcements WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM staff_members WHERE name LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM agenda_items WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM school_documents WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM school_achievements WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM extracurriculars WHERE name LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM school_facilities WHERE name LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM faqs WHERE question LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM ppdb_entries WHERE title LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM contact_messages WHERE subject LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM ppdb_applications WHERE student_name LIKE :marker', ['marker' => $marker . '%']],
        ['DELETE FROM admins WHERE username LIKE :marker', ['marker' => strtolower($marker) . '%']],
    ];

    foreach ($queries as [$sql, $params]) {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
    }
}

if (!db_available()) {
    smoke_fail('Database belum terhubung. Aktifkan MySQL/XAMPP sebelum smoke test.');
    exit(1);
}

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_role'] = 'superadmin';

$marker = 'SMOKE-' . date('YmdHis') . '-';
$image = '/public/logo-sekolah.png';
$year = current_academic_year();

smoke_cleanup('SMOKE-');

try {
    smoke_assert(admin_can('settings') && admin_can('db_backup'), 'Role superadmin punya akses fitur sensitif.');
    $ppdbPermissions = admin_permissions()['ppdb'];
    smoke_assert(in_array('ppdb_applications', $ppdbPermissions, true) && !in_array('documents', $ppdbPermissions, true) && !in_array('faqs', $ppdbPermissions, true), 'Role PPDB dibatasi ke modul PPDB.');

    $adminUsername = strtolower($marker) . 'editor';
    $adminResult = save_admin_user([
        'id' => null,
        'username' => $adminUsername,
        'full_name' => $marker . 'Admin Test',
        'role' => 'editor',
        'is_active' => 1,
        'password' => 'SmokeTest123',
    ]);
    smoke_assert($adminResult['success'] && smoke_id('admins', 'username', $adminUsername), 'Form akun admin bisa membuat akun uji.');
    $adminId = smoke_id('admins', 'username', $adminUsername);
    if ($adminId) {
        $deleteAdmin = delete_admin_user($adminId);
        smoke_assert($deleteAdmin['success'] && !admin_user($adminId), 'Form akun admin bisa menghapus akun uji.');
    }

    $newsTitle = $marker . 'Berita';
    save_news([
        'id' => null,
        'slug' => strtolower($marker) . 'berita',
        'title' => $newsTitle,
        'category' => 'Uji',
        'excerpt' => 'Ringkasan uji berita.',
        'content' => 'Konten uji berita.',
        'image_path' => $image,
        'published_at' => date('Y-m-d H:i:s'),
        'is_active' => 1,
    ]);
    $newsId = smoke_id('news', 'title', $newsTitle);
    smoke_assert((bool) $newsId, 'Form berita bisa menyimpan data publish.');
    smoke_assert(news_item_by_slug(strtolower($marker) . 'berita') !== null, 'Berita publish tampil di fungsi publik.');
    if ($newsId) {
        delete_news($newsId);
        smoke_assert(admin_news_row($newsId) === null, 'Form berita bisa menghapus data uji.');
    }

    $draftTitle = $marker . 'Berita Draft';
    save_news([
        'id' => null,
        'slug' => strtolower($marker) . 'berita-draft',
        'title' => $draftTitle,
        'category' => 'Uji',
        'excerpt' => 'Ringkasan uji draft.',
        'content' => 'Konten uji draft.',
        'image_path' => $image,
        'published_at' => date('Y-m-d H:i:s'),
        'is_active' => 0,
    ]);
    smoke_assert(news_item_by_slug(strtolower($marker) . 'berita-draft') === null, 'Berita draft tidak tampil di publik.');

    $galleryTitle = $marker . 'Galeri';
    save_gallery_item(['id' => null, 'title' => $galleryTitle, 'description' => 'Deskripsi galeri uji.', 'image_path' => $image, 'sort_order' => 999]);
    $galleryId = smoke_id('gallery_items', 'title', $galleryTitle);
    smoke_assert((bool) $galleryId, 'Form galeri bisa menyimpan data.');
    if ($galleryId) {
        delete_gallery_item($galleryId);
        smoke_assert(admin_gallery_row($galleryId) === null, 'Form galeri bisa menghapus data uji.');
    }

    $announcementTitle = $marker . 'Pengumuman';
    save_announcement([
        'id' => null,
        'title' => $announcementTitle,
        'content' => 'Isi pengumuman uji.',
        'link_label' => '',
        'link_url' => '',
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => 1,
        'sort_order' => 999,
    ]);
    $announcementId = smoke_id('announcements', 'title', $announcementTitle);
    smoke_assert((bool) $announcementId, 'Form pengumuman bisa menyimpan data.');
    if ($announcementId) {
        delete_announcement($announcementId);
        smoke_assert(admin_announcement_row($announcementId) === null, 'Form pengumuman bisa menghapus data uji.');
    }

    $staffName = $marker . 'Guru';
    save_staff_member([
        'id' => null,
        'name' => $staffName,
        'position' => 'Guru Uji',
        'subject' => 'Uji Sistem',
        'bio' => 'Bio uji.',
        'phone' => '',
        'email' => '',
        'photo_path' => $image,
        'is_active' => 1,
        'sort_order' => 999,
    ]);
    $staffId = smoke_id('staff_members', 'name', $staffName);
    smoke_assert((bool) $staffId, 'Form guru dan staf bisa menyimpan data.');
    if ($staffId) {
        delete_staff_member($staffId);
        smoke_assert(admin_staff_row($staffId) === null, 'Form guru dan staf bisa menghapus data uji.');
    }

    $agendaTitle = $marker . 'Agenda';
    save_agenda_item([
        'id' => null,
        'title' => $agendaTitle,
        'category' => 'Uji',
        'location' => 'Sekolah',
        'description' => 'Agenda uji.',
        'starts_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'ends_at' => null,
        'is_active' => 1,
        'academic_year' => $year,
        'sort_order' => 999,
    ]);
    $agendaId = smoke_id('agenda_items', 'title', $agendaTitle);
    smoke_assert((bool) $agendaId, 'Form agenda bisa menyimpan data.');
    if ($agendaId) {
        delete_agenda_item($agendaId);
        smoke_assert(admin_agenda_row($agendaId) === null, 'Form agenda bisa menghapus data uji.');
    }

    $documentTitle = $marker . 'Dokumen';
    save_school_document([
        'id' => null,
        'title' => $documentTitle,
        'category' => 'Formulir',
        'description' => 'Dokumen uji.',
        'file_path' => 'storage/uploads/documents/smoke-test.pdf',
        'original_filename' => 'smoke-test.pdf',
        'file_size' => 123,
        'is_active' => 1,
        'academic_year' => $year,
        'sort_order' => 999,
    ]);
    $documentId = smoke_id('school_documents', 'title', $documentTitle);
    smoke_assert((bool) $documentId, 'Form dokumen bisa menyimpan metadata PDF.');
    if ($documentId) {
        delete_school_document($documentId);
        smoke_assert(admin_document_row($documentId) === null, 'Form dokumen bisa menghapus data uji.');
    }

    $achievementTitle = $marker . 'Prestasi';
    save_achievement([
        'id' => null,
        'title' => $achievementTitle,
        'level' => 'Sekolah',
        'category' => 'Akademik',
        'achiever_name' => 'Siswa Uji',
        'achievement_date' => date('Y-m-d'),
        'description' => 'Prestasi uji.',
        'image_path' => $image,
        'is_active' => 1,
        'academic_year' => $year,
        'sort_order' => 999,
    ]);
    $achievementId = smoke_id('school_achievements', 'title', $achievementTitle);
    smoke_assert((bool) $achievementId, 'Form prestasi bisa menyimpan data.');
    if ($achievementId) {
        delete_achievement($achievementId);
        smoke_assert(admin_achievement_row($achievementId) === null, 'Form prestasi bisa menghapus data uji.');
    }

    $extracurricularName = $marker . 'Ekskul';
    save_extracurricular([
        'id' => null,
        'name' => $extracurricularName,
        'category' => 'Kesiswaan',
        'coach' => 'Pembina Uji',
        'schedule_text' => 'Jumat',
        'location' => 'Lapangan',
        'description' => 'Ekskul uji.',
        'image_path' => $image,
        'is_active' => 1,
        'sort_order' => 999,
    ]);
    $extracurricularId = smoke_id('extracurriculars', 'name', $extracurricularName);
    smoke_assert((bool) $extracurricularId, 'Form ekstrakurikuler bisa menyimpan data.');
    if ($extracurricularId) {
        delete_extracurricular($extracurricularId);
        smoke_assert(admin_extracurricular_row($extracurricularId) === null, 'Form ekstrakurikuler bisa menghapus data uji.');
    }

    $facilityName = $marker . 'Fasilitas';
    save_facility([
        'id' => null,
        'name' => $facilityName,
        'category' => 'Sarana',
        'location' => 'Gedung Uji',
        'capacity' => '10 orang',
        'condition_status' => 'Baik',
        'description' => 'Fasilitas uji.',
        'image_path' => $image,
        'is_active' => 1,
        'sort_order' => 999,
    ]);
    $facilityId = smoke_id('school_facilities', 'name', $facilityName);
    smoke_assert((bool) $facilityId, 'Form fasilitas bisa menyimpan data.');
    if ($facilityId) {
        delete_facility($facilityId);
        smoke_assert(admin_facility_row($facilityId) === null, 'Form fasilitas bisa menghapus data uji.');
    }

    $faqQuestion = $marker . 'FAQ?';
    save_faq([
        'id' => null,
        'question' => $faqQuestion,
        'answer' => 'Jawaban uji.',
        'category' => 'Umum',
        'is_active' => 1,
        'sort_order' => 999,
    ]);
    $faqId = smoke_id('faqs', 'question', $faqQuestion);
    smoke_assert((bool) $faqId, 'Form FAQ bisa menyimpan data.');
    if ($faqId) {
        delete_faq($faqId);
        smoke_assert(admin_faq_row($faqId) === null, 'Form FAQ bisa menghapus data uji.');
    }

    $ppdbEntryTitle = $marker . 'Konten PPDB';
    save_ppdb_entry(['section' => 'informasi', 'title' => $ppdbEntryTitle, 'content' => 'Konten PPDB uji.', 'sort_order' => 999]);
    $ppdbEntryId = smoke_id('ppdb_entries', 'title', $ppdbEntryTitle);
    smoke_assert((bool) $ppdbEntryId, 'Form konten PPDB bisa menyimpan data.');
    if ($ppdbEntryId) {
        delete_ppdb_entry($ppdbEntryId);
        smoke_assert(admin_ppdb_row($ppdbEntryId) === null, 'Form konten PPDB bisa menghapus data uji.');
    }

    $contactSubject = $marker . 'Pesan Kontak';
    save_contact_message([
        'name' => $marker . 'Pengirim',
        'email' => 'smoke@example.test',
        'phone' => '0800000000',
        'subject' => $contactSubject,
        'message' => 'Pesan uji.',
    ]);
    $contactId = smoke_id('contact_messages', 'subject', $contactSubject);
    smoke_assert((bool) $contactId, 'Form kontak publik bisa menyimpan pesan.');
    if ($contactId) {
        update_contact_message_status($contactId, 'selesai');
        $row = db()->query('SELECT status FROM contact_messages WHERE id = ' . (int) $contactId)->fetch();
        smoke_assert(($row['status'] ?? '') === 'selesai', 'Status pesan kontak bisa diperbarui admin.');
        delete_contact_message($contactId);
        smoke_assert(smoke_id('contact_messages', 'subject', $contactSubject) === null, 'Pesan kontak bisa dihapus admin.');
    }

    $studentName = $marker . 'Calon Siswa';
    $registrationNumber = save_ppdb_application([
        'student_name' => $studentName,
        'birth_place' => 'Kotabaru',
        'birth_date' => '2013-01-01',
        'gender' => 'Laki-laki',
        'nisn' => '0099999999',
        'previous_school' => 'SD Uji',
        'parent_name' => 'Orang Tua Uji',
        'parent_phone' => '081234567890',
        'parent_email' => 'orangtua@example.test',
        'address' => 'Alamat uji',
        'family_card_path' => 'storage/uploads/ppdb/kk-smoke.pdf',
        'birth_certificate_path' => 'storage/uploads/ppdb/akta-smoke.pdf',
        'report_card_path' => 'storage/uploads/ppdb/rapor-smoke.pdf',
        'photo_path' => 'storage/uploads/ppdb/foto-smoke.jpg',
        'consent_accepted_at' => date('Y-m-d H:i:s'),
        'privacy_version' => '2026-04-30',
        'academic_year' => $year,
    ]);
    smoke_assert($registrationNumber !== '', 'Form PPDB publik menghasilkan nomor pendaftaran.');
    $applicationId = smoke_id('ppdb_applications', 'registration_number', $registrationNumber);
    smoke_assert((bool) $applicationId, 'Data pendaftar PPDB tersimpan di admin.');
    smoke_assert(ppdb_application_by_registration($registrationNumber, 'orangtua@example.test') !== null, 'Bukti pendaftaran PPDB bisa dicari dengan nomor dan email.');
    if ($applicationId) {
        update_ppdb_application_status($applicationId, 'diverifikasi', 'Smoke test.');
        $filtered = admin_ppdb_applications(['q' => $registrationNumber, 'status' => 'diverifikasi', 'academic_year' => $year]);
        smoke_assert(count($filtered) === 1, 'Filter dan pencarian pendaftar PPDB berjalan.');
        delete_ppdb_application($applicationId);
        smoke_assert(admin_ppdb_application($applicationId) === null, 'Data pendaftar PPDB bisa dihapus admin.');
    }
} catch (Throwable $exception) {
    smoke_fail($exception->getMessage());
} finally {
    smoke_cleanup('SMOKE-');
}

echo PHP_EOL . 'Total OK: ' . count($passes) . PHP_EOL;
echo 'Total FAIL: ' . count($failures) . PHP_EOL;

exit($failures ? 1 : 0);
