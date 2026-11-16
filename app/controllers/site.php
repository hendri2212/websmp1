<?php

$path = current_path();
require_http_methods($path === '/kontak' ? ['GET', 'HEAD', 'POST'] : ['GET', 'HEAD']);
if (!db_available()) {
    send_generic_error_response(503);
    exit;
}
$content = site_content();
$pageTitle = $content['schoolProfile']['name'];
$metaDescription = $content['schoolProfile']['description'] ?? '';
$metaImage = $content['schoolProfile']['logoPath'] ?? '';
$news = news_items();
$gallery = gallery_items();
$newsItem = null;
$achievementItem = null;

if (!empty($content['siteMaintenance']['enabled']) && !in_array($path, ['/robots.txt', '/sitemap.xml'], true)) {
    http_response_code(503);
    header('Retry-After: 3600');
    $pageTitle = 'Website Sedang Dalam Pemeliharaan - ' . $content['schoolProfile']['name'];
    $metaDescription = plain_text_excerpt($content['siteMaintenance']['message'], 160);
    $view = 'site/maintenance.php';
    render_view($view, get_defined_vars(), 'site');
    exit;
}

switch (true) {
    case $path === '/sitemap.xml':
        require APP_ROOT . '/app/controllers/files/sitemap.php';
        exit;
    case $path === '/robots.txt':
        require APP_ROOT . '/app/controllers/files/robots.php';
        exit;
    case $path === '/':
        $metaDescription = $content['schoolProfile']['heroDescription'] ?? $metaDescription;
        $view = 'site/home.php';
        break;
    case $path === '/profil':
        $pageTitle = 'Profil - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Profil resmi, sejarah, visi, misi, identitas, dan sambutan kepala sekolah ' . $content['schoolProfile']['name'] . '.';
        $view = 'site/profil.php';
        break;
    case $path === '/fasilitas':
        $pageTitle = 'Fasilitas - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi fasilitas dan sarana prasarana pendukung pembelajaran di ' . $content['schoolProfile']['name'] . '.';
        $view = 'site/fasilitas.php';
        break;
    case $path === '/akademik':
        $pageTitle = 'Akademik - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi akademik, kurikulum, pembelajaran, asesmen, dan layanan pendampingan belajar sekolah.';
        $view = 'site/akademik.php';
        break;
    case $path === '/agenda':
        $pageTitle = 'Agenda - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Agenda dan kalender kegiatan sekolah yang dapat diikuti warga sekolah dan masyarakat.';
        $view = 'site/agenda.php';
        break;
    case $path === '/dokumen':
        $pageTitle = 'Dokumen - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Download dokumen resmi sekolah seperti kalender akademik, formulir, surat edaran, tata tertib, dan PDF sekolah.';
        $view = 'site/dokumen.php';
        break;
    case $path === '/prestasi':
        $pageTitle = 'Prestasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Daftar prestasi akademik dan non-akademik peserta didik, guru, dan sekolah.';
        $view = 'site/prestasi.php';
        break;
    case $path === '/cari':
        $searchQuery = trim($_GET['q'] ?? '');
        $pageTitle = 'Pencarian - ' . $content['schoolProfile']['name'];
        $metaDescription = $searchQuery !== ''
            ? 'Hasil pencarian untuk ' . $searchQuery . ' di website resmi ' . $content['schoolProfile']['name'] . '.'
            : 'Pencarian konten website resmi ' . $content['schoolProfile']['name'] . '.';
        $view = 'site/cari.php';
        break;
    case preg_match('#^/prestasi/(\d+)$#', $path, $matches):
        $achievementId = database_id($matches[1]);
        $achievementItem = $achievementId !== null ? achievement_public_row($achievementId) : null;
        if (!$achievementItem) {
            http_response_code(404);
            $pageTitle = 'Halaman Tidak Ditemukan';
            $view = 'site/404.php';
        } else {
            $pageTitle = $achievementItem['title'] . ' - ' . $content['schoolProfile']['name'];
            $metaDescription = plain_text_excerpt($achievementItem['description'], 160);
            $metaImage = $achievementItem['image_path'] ?? $metaImage;
            $view = 'site/prestasi_detail.php';
        }
        break;
    case $path === '/dokumen/unduh':
        require APP_ROOT . '/app/controllers/files/document_download.php';
        exit;
    case $path === '/akreditasi/unduh':
        redirect_to('akreditasi/lihat');
    case $path === '/akreditasi/file':
        require APP_ROOT . '/app/controllers/files/accreditation_file.php';
        exit;
    case $path === '/akreditasi/lihat':
        $pageTitle = 'Lembar Akreditasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Tampilan lembar akreditasi resmi sekolah untuk dilihat pengunjung website.';
        $view = 'site/accreditation_view.php';
        break;
    case $path === '/guru-staf':
        $pageTitle = 'Guru & Staf - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Data guru dan staf ' . $content['schoolProfile']['name'] . ' sebagai informasi pendidik dan tenaga kependidikan.';
        $view = 'site/guru_staf.php';
        break;
    case $path === '/kesiswaan':
        $pageTitle = 'Kesiswaan - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi kesiswaan, organisasi siswa, pembinaan karakter, dan pengembangan potensi peserta didik.';
        $view = 'site/kesiswaan.php';
        break;
    case $path === '/ekstrakurikuler':
        $pageTitle = 'Ekstrakurikuler - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Pilihan kegiatan ekstrakurikuler sekolah beserta pembina, jadwal, lokasi, dan deskripsi kegiatan.';
        $view = 'site/ekstrakurikuler.php';
        break;
    case $path === '/berita':
        $pageTitle = 'Berita - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Berita, pengumuman, dan publikasi kegiatan terbaru dari ' . $content['schoolProfile']['name'] . '.';
        $view = 'site/berita.php';
        break;
    case str_starts_with($path, '/berita/'):
        $slug = rawurldecode(basename($path));
        $newsItem = news_item_by_slug($slug);
        if (!$newsItem) {
            http_response_code(404);
            $pageTitle = 'Halaman Tidak Ditemukan';
            $view = 'site/404.php';
        } else {
            $pageTitle = $newsItem['title'] . ' - ' . $content['schoolProfile']['name'];
            $metaDescription = $newsItem['desc'];
            $metaImage = $newsItem['image_path'] ?? $metaImage;
            $view = 'site/berita_detail.php';
        }
        break;
    case $path === '/galeri':
        $pageTitle = 'Galeri - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Galeri dokumentasi kegiatan, fasilitas, dan publikasi visual sekolah.';
        $view = 'site/galeri.php';
        break;
    case $path === '/ppdb':
        $pageTitle = 'PPDB - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi PPDB/SPMB ' . $content['schoolProfile']['name'] . '. Pendaftaran resmi ditangani Dinas Pendidikan.';
        $view = 'site/ppdb.php';
        break;
    case $path === '/kontak':
        $pageTitle = 'Kontak - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Alamat, telepon, email, jam layanan, dan formulir kontak resmi sekolah.';
        $view = 'site/kontak.php';
        break;
    case $path === '/faq':
        $pageTitle = 'FAQ - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Pertanyaan umum dan jawaban resmi mengenai layanan sekolah, PPDB, akademik, administrasi, dan kesiswaan.';
        $view = 'site/faq.php';
        break;
    case $path === '/kebijakan-privasi':
        $pageTitle = 'Kebijakan Privasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Kebijakan privasi penggunaan data pribadi untuk layanan informasi dan komunikasi website sekolah.';
        $view = 'site/kebijakan_privasi.php';
        break;
    default:
        http_response_code(404);
        $pageTitle = 'Halaman Tidak Ditemukan';
        $metaDescription = 'Halaman yang Anda cari tidak ditemukan di website resmi sekolah.';
        $view = 'site/404.php';
        break;
}

switch ($view) {
    case 'site/prestasi_detail.php':
        $relatedAchievements = related_achievements((int) $achievementItem['id'], $achievementItem['category'], 3);
        break;
    case 'site/berita_detail.php':
        $relatedNews = related_news_items($newsItem['slug'], $newsItem['category'], 3);
        break;
    case 'site/accreditation_view.php':
        $accreditation = $content['schoolAccreditation'] ?? [];
        $filePath = trim((string) ($accreditation['filePath'] ?? ''));
        $status = trim((string) ($accreditation['status'] ?? ''));
        $skNumber = trim((string) ($accreditation['skNumber'] ?? ''));
        $skDate = trim((string) ($accreditation['skDate'] ?? ''));
        break;
    case 'site/dokumen.php':
        $selectedCategory = trim($_GET['category'] ?? '');
        $categories = document_categories();
        $documents = public_documents($selectedCategory !== '' ? $selectedCategory : null);
        break;
    case 'site/kontak.php':
        $contactMessage = '';
        $contactError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim(input_form_value($_POST, 'name'));
            $email = trim(input_form_value($_POST, 'email'));
            $phone = trim(input_form_value($_POST, 'phone'));
            $subject = trim(input_form_value($_POST, 'subject'));
            $visitorMessage = trim(input_form_value($_POST, 'message'));

            if (!db_available()) {
                $contactError = 'Form kontak belum aktif karena database belum terhubung.';
            } elseif (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
                $contactError = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
            } elseif (($validationError = input_validation_error('contact', $_POST)) !== '') {
                $contactError = $validationError;
            } elseif ($name === '' || $email === '' || $subject === '' || $visitorMessage === '') {
                $contactError = 'Nama, email, subjek, dan pesan wajib diisi.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contactError = 'Format email belum valid.';
            } else {
                save_contact_message([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $visitorMessage,
                ]);
                $contactMessage = 'Pesan berhasil dikirim. Terima kasih sudah menghubungi sekolah.';
                $_POST = [];
            }
        }
        break;
    case 'site/home.php':
        $school = $content['schoolProfile'];
        $principalPhotoPath = $content['principalMessage']['photoPath'] ?? '';
        $homeSettings = $content['siteHome'] ?? [];
        $heroAchievements = array_slice(home_achievements(), 0, 3);
        $homeAnnouncements = ($homeSettings['showAnnouncements'] ?? true) ? active_announcements() : [];
        $teacherLimit = max(4, min(24, (int) ($homeSettings['teacherLimit'] ?? 12)));
        $agendaLimit = max(1, min(9, (int) ($homeSettings['agendaLimit'] ?? 3)));
        $upcomingAgenda = upcoming_agenda_items($agendaLimit);
        $homeStaffMembers = array_slice(staff_members(), 0, $teacherLimit);
        $heroTitle = trim($homeSettings['heroTitle'] ?? '') ?: $school['name'];
        $heroSubtitle = trim($homeSettings['heroSubtitle'] ?? '') ?: $school['heroDescription'];
        $primaryButtonLabel = trim($homeSettings['primaryButtonLabel'] ?? '') ?: '';
        $primaryButtonUrl = trim($homeSettings['primaryButtonUrl'] ?? '') ?: '/profil';
        $secondaryButtonLabel = trim($homeSettings['secondaryButtonLabel'] ?? '') ?: '';
        $secondaryButtonUrl = trim($homeSettings['secondaryButtonUrl'] ?? '') ?: '/ppdb';
        $profileVideoUrl = video_embed_url($homeSettings['profileVideoUrl'] ?? '');
        $profileYoutubeId = youtube_video_id($homeSettings['profileVideoUrl'] ?? '');
        $profileYoutubeWatchUrl = $profileYoutubeId === null ? '' : 'https://www.youtube.com/watch?v=' . $profileYoutubeId;
        $profileVideoFileUrl = video_file_url($homeSettings['profileVideoUrl'] ?? '');
        $profileVideoTitle = trim($homeSettings['profileVideoTitle'] ?? '') ?: '';
        $profileVideoDescription = trim($homeSettings['profileVideoDescription'] ?? '') ?: '';
        $primaryButtonHref = content_link_url($primaryButtonUrl);
        $secondaryButtonHref = content_link_url($secondaryButtonUrl);
        $showStats = $homeSettings['showStats'] ?? true;
        $showPrincipalCard = $homeSettings['showPrincipalCard'] ?? true;

        break;
    case 'site/prestasi.php':
        $selectedCategory = trim($_GET['category'] ?? '');
        $categories = achievement_categories_all_years();
        $achievementRows = achievement_rows_all_years($selectedCategory !== '' ? $selectedCategory : null);
        break;
    case 'site/fasilitas.php':
        $selectedCategory = trim($_GET['category'] ?? '');
        $categories = facility_categories();
        $facilityRows = school_facilities($selectedCategory !== '' ? $selectedCategory : null);
        break;
    case 'site/ekstrakurikuler.php':
        $selectedCategory = trim($_GET['category'] ?? '');
        $categories = extracurricular_categories();
        $extracurricularRows = extracurriculars($selectedCategory !== '' ? $selectedCategory : null);
        break;
    case 'site/faq.php':
        $selectedCategory = trim($_GET['category'] ?? '');
        $categories = faq_categories();
        $faqRows = faqs($selectedCategory !== '' ? $selectedCategory : null);
        break;
    case 'site/ppdb.php':
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            http_response_code(405);
            header('Allow: GET, HEAD');
            exit('Halaman ini hanya menyediakan informasi PPDB/SPMB.');
        }
        if (!isset($content) || !function_exists('ppdb_sections_data')) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('Halaman tidak ditemukan.');
        }
        unset($_SESSION['ppdb_receipt']);
        $ppdbSections = ppdb_sections_data();
        $ppdbIntroductions = [];
        foreach ($ppdbSections['pengantar'] ?? [] as $item) {
            $ppdbIntroductions[$item['title']] = str_replace('{tahun_ajaran}', current_academic_year(), $item['desc']);
        }
        $ppdbSteps = isset($ppdbSections['alur']) ? array_column($ppdbSections['alur'], 'desc') : [];
        $ppdbDocuments = public_documents('PPDB');
        $ppdbContactItems = $ppdbSections['kontak'] ?? [];
        if (!$ppdbContactItems) {
            $ppdbContactItems = array_filter($content['contactChannels'] ?? [], static function (array $item): bool {
                return in_array($item['title'] ?? '', ['Telepon', 'Email Resmi', 'Jam Layanan'], true);
            });
        }
        break;
    case 'site/guru_staf.php':
        $staffMembers = staff_members();
        break;
    case 'site/agenda.php':
        $agendaRows = agenda_items();
        break;
    case 'site/cari.php':
        $searchQuery = trim($_GET['q'] ?? '');
        $searchResults = $searchQuery !== '' ? public_search_results($searchQuery) : [];
        break;
}

render_view($view, get_defined_vars(), 'site');
