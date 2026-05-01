<?php

require __DIR__ . '/includes/bootstrap.php';

$content = site_content();
$path = current_path();
$pageTitle = $content['schoolProfile']['name'];
$metaDescription = $content['schoolProfile']['description'] ?? '';
$metaImage = $content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png';
$news = news_items();
$gallery = gallery_items();
$newsItem = null;
<<<<<<< HEAD
$achievementItem = null;
=======
>>>>>>> 98147e3 (upload pertama)

if (!empty($content['siteMaintenance']['enabled']) && !in_array($path, ['/robots.txt', '/sitemap.xml'], true)) {
    http_response_code(503);
    header('Retry-After: 3600');
    $pageTitle = 'Website Sedang Dalam Pemeliharaan - ' . $content['schoolProfile']['name'];
    $metaDescription = plain_text_excerpt($content['siteMaintenance']['message'] ?? 'Website sekolah sedang dalam pemeliharaan singkat.', 160);
    $view = 'pages/maintenance.php';
    require __DIR__ . '/templates/header.php';
    require __DIR__ . '/' . $view;
    require __DIR__ . '/templates/footer.php';
    exit;
}

switch (true) {
    case $path === '/sitemap.xml':
        require __DIR__ . '/pages/sitemap.php';
        exit;
    case $path === '/robots.txt':
        require __DIR__ . '/pages/robots.php';
        exit;
    case $path === '/':
        $metaDescription = $content['schoolProfile']['heroDescription'] ?? $metaDescription;
        $view = 'pages/home.php';
        break;
    case $path === '/profil':
        $pageTitle = 'Profil - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Profil resmi, sejarah, visi, misi, identitas, dan sambutan kepala sekolah ' . $content['schoolProfile']['name'] . '.';
        $view = 'pages/profil.php';
        break;
    case $path === '/fasilitas':
        $pageTitle = 'Fasilitas - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi fasilitas dan sarana prasarana pendukung pembelajaran di ' . $content['schoolProfile']['name'] . '.';
        $view = 'pages/fasilitas.php';
        break;
    case $path === '/akademik':
        $pageTitle = 'Akademik - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi akademik, kurikulum, pembelajaran, asesmen, dan layanan pendampingan belajar sekolah.';
        $view = 'pages/akademik.php';
        break;
    case $path === '/agenda':
        $pageTitle = 'Agenda - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Agenda dan kalender kegiatan sekolah yang dapat diikuti warga sekolah dan masyarakat.';
        $view = 'pages/agenda.php';
        break;
    case $path === '/dokumen':
        $pageTitle = 'Dokumen - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Download dokumen resmi sekolah seperti kalender akademik, formulir, surat edaran, tata tertib, dan PDF sekolah.';
        $view = 'pages/dokumen.php';
        break;
    case $path === '/prestasi':
        $pageTitle = 'Prestasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Daftar prestasi akademik dan non-akademik peserta didik, guru, dan sekolah.';
        $view = 'pages/prestasi.php';
        break;
<<<<<<< HEAD
    case $path === '/cari':
        $searchQuery = trim($_GET['q'] ?? '');
        $pageTitle = 'Pencarian - ' . $content['schoolProfile']['name'];
        $metaDescription = $searchQuery !== ''
            ? 'Hasil pencarian untuk ' . $searchQuery . ' di website resmi ' . $content['schoolProfile']['name'] . '.'
            : 'Pencarian konten website resmi ' . $content['schoolProfile']['name'] . '.';
        $view = 'pages/cari.php';
        break;
    case preg_match('#^/prestasi/(\d+)$#', $path, $matches):
        $achievementItem = achievement_public_row((int) $matches[1]);
        if (!$achievementItem) {
            http_response_code(404);
            $pageTitle = 'Halaman Tidak Ditemukan';
            $view = 'pages/404.php';
        } else {
            $pageTitle = $achievementItem['title'] . ' - ' . $content['schoolProfile']['name'];
            $metaDescription = plain_text_excerpt($achievementItem['description'], 160);
            $metaImage = $achievementItem['image_path'] ?? $metaImage;
            $view = 'pages/prestasi_detail.php';
        }
        break;
=======
>>>>>>> 98147e3 (upload pertama)
    case $path === '/dokumen/unduh':
        require __DIR__ . '/pages/document_download.php';
        exit;
    case $path === '/akreditasi/unduh':
        redirect_to('akreditasi/lihat');
    case $path === '/akreditasi/file':
        require __DIR__ . '/pages/accreditation_file.php';
        exit;
    case $path === '/akreditasi/lihat':
        $pageTitle = 'Lembar Akreditasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Tampilan lembar akreditasi resmi sekolah untuk dilihat pengunjung website.';
        $view = 'pages/accreditation_view.php';
        break;
    case $path === '/guru-staf':
        $pageTitle = 'Guru & Staf - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Data guru dan staf ' . $content['schoolProfile']['name'] . ' sebagai informasi pendidik dan tenaga kependidikan.';
        $view = 'pages/guru_staf.php';
        break;
    case $path === '/kesiswaan':
        $pageTitle = 'Kesiswaan - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi kesiswaan, organisasi siswa, pembinaan karakter, dan pengembangan potensi peserta didik.';
        $view = 'pages/kesiswaan.php';
        break;
    case $path === '/ekstrakurikuler':
        $pageTitle = 'Ekstrakurikuler - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Pilihan kegiatan ekstrakurikuler sekolah beserta pembina, jadwal, lokasi, dan deskripsi kegiatan.';
        $view = 'pages/ekstrakurikuler.php';
        break;
    case $path === '/berita':
        $pageTitle = 'Berita - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Berita, pengumuman, dan publikasi kegiatan terbaru dari ' . $content['schoolProfile']['name'] . '.';
        $view = 'pages/berita.php';
        break;
    case str_starts_with($path, '/berita/'):
        $slug = basename($path);
        $newsItem = news_item_by_slug($slug);
        if (!$newsItem) {
            http_response_code(404);
            $pageTitle = 'Halaman Tidak Ditemukan';
            $view = 'pages/404.php';
        } else {
            $pageTitle = $newsItem['title'] . ' - ' . $content['schoolProfile']['name'];
            $metaDescription = $newsItem['desc'];
            $metaImage = $newsItem['image_path'] ?? $metaImage;
            $view = 'pages/berita_detail.php';
        }
        break;
    case $path === '/galeri':
        $pageTitle = 'Galeri - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Galeri dokumentasi kegiatan, fasilitas, dan publikasi visual sekolah.';
        $view = 'pages/galeri.php';
        break;
    case $path === '/ppdb':
        $pageTitle = 'PPDB - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Informasi dan formulir pendaftaran peserta didik baru ' . $content['schoolProfile']['name'] . '.';
        $view = 'pages/ppdb.php';
        break;
    case $path === '/ppdb/bukti':
        $pageTitle = 'Bukti PPDB - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Cetak dan cek bukti pendaftaran PPDB resmi sekolah.';
        $view = 'pages/ppdb_receipt.php';
        break;
    case $path === '/kontak':
        $pageTitle = 'Kontak - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Alamat, telepon, email, jam layanan, dan formulir kontak resmi sekolah.';
        $view = 'pages/kontak.php';
        break;
    case $path === '/faq':
        $pageTitle = 'FAQ - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Pertanyaan umum dan jawaban resmi mengenai layanan sekolah, PPDB, akademik, administrasi, dan kesiswaan.';
        $view = 'pages/faq.php';
        break;
    case $path === '/kebijakan-privasi':
        $pageTitle = 'Kebijakan Privasi - ' . $content['schoolProfile']['name'];
        $metaDescription = 'Kebijakan privasi penggunaan data pribadi untuk layanan website sekolah dan pendaftaran PPDB online.';
        $view = 'pages/kebijakan_privasi.php';
        break;
    default:
        http_response_code(404);
        $pageTitle = 'Halaman Tidak Ditemukan';
        $metaDescription = 'Halaman yang Anda cari tidak ditemukan di website resmi sekolah.';
        $view = 'pages/404.php';
        break;
}

require __DIR__ . '/templates/header.php';
require __DIR__ . '/' . $view;
require __DIR__ . '/templates/footer.php';
