<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

if (PHP_SAPI === 'cli') {
    $sessionPath = __DIR__ . '/../tests/.tmp_sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    session_save_path($sessionPath);
}

require_once __DIR__ . '/../includes/bootstrap.php';

if (!db_available()) {
    fwrite(STDERR, 'Database belum terhubung.' . PHP_EOL);
    exit(1);
}

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_role'] = 'superadmin';

$pdo = db();
$content = site_content();
$year = current_academic_year();
$logo = $content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png';
$principalPhoto = $content['principalMessage']['photoPath'] ?? '/public/kepala-sekolah.jpeg';

function seed_exists(string $table, string $column, string $value): bool
{
    $allowed = [
        'news' => ['slug', 'title'],
        'gallery_items' => ['title'],
        'announcements' => ['title'],
        'agenda_items' => ['title'],
        'school_achievements' => ['title'],
        'extracurriculars' => ['name'],
        'faqs' => ['question'],
        'ppdb_entries' => ['section_title'],
    ];

    if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
        throw new InvalidArgumentException('Kolom seed tidak diizinkan.');
    }

    if ($table === 'ppdb_entries' && $column === 'section_title') {
        [$section, $title] = explode('|', $value, 2);
        $statement = db()->prepare('SELECT COUNT(*) FROM ppdb_entries WHERE section = :section AND title = :title');
        $statement->execute(['section' => $section, 'title' => $title]);
        return (int) $statement->fetchColumn() > 0;
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM ' . repository_quote_identifier($table) .
        ' WHERE ' . repository_quote_identifier($column) . ' = :value'
    );
    $statement->execute(['value' => $value]);

    return (int) $statement->fetchColumn() > 0;
}

function seed_done(string $message): void
{
    echo '[OK] ' . $message . PHP_EOL;
}

function seed_skip(string $message): void
{
    echo '[SKIP] ' . $message . PHP_EOL;
}

function seed_when_missing(string $table, string $column, string $value, callable $callback, string $label): void
{
    if (seed_exists($table, $column, $value)) {
        seed_skip($label . ' sudah ada.');
        return;
    }

    $callback();
    seed_done($label . ' ditambahkan.');
}

function seed_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string) $slug, '-');
}

ensure_academic_year_columns();
ensure_news_publication_column();

$identity = $content['schoolIdentity'] ?? [];
$identityByLabel = [];
foreach ($identity as $row) {
    if (!empty($row['label'])) {
        $identityByLabel[$row['label']] = $row['value'] ?? '';
    }
}

$identityUpdates = [
    'Klasifikasi Geografis' => 'Perkotaan',
    'Waktu Penyelenggaraan' => 'Pagi',
    'Tahun Renovasi Terakhir' => '1995',
    'SK Status Sekolah' => 'No. 3326/B/1954 tanggal 21 Agustus 1954',
    'SK/Izin Pendirian' => 'No. 421.3/892/BID.SMP/DIKBUD tanggal 5 Agustus 2016',
    'Data Siswa Desember 2025' => '775 siswa, terdiri dari 376 laki-laki dan 399 perempuan dalam 24 rombongan belajar',
];
foreach ($identityUpdates as $label => $value) {
    $identityByLabel[$label] = $value;
}

$updatedIdentity = [];
foreach ($identityByLabel as $label => $value) {
    $updatedIdentity[] = ['label' => $label, 'value' => $value];
}

save_site_sections([
    'stats' => [
        ['label' => 'Siswa', 'value' => '775'],
        ['label' => 'Guru & Staf', 'value' => '65'],
        ['label' => 'Ekstrakurikuler', 'value' => '19'],
        ['label' => 'Rombel', 'value' => '24'],
    ],
    'schoolIdentity' => $updatedIdentity,
]);
seed_done('Statistik dan identitas sekolah diperbarui dari laporan bulanan Desember 2025.');

$announcements = [
    [
        'title' => 'Layanan Informasi Sekolah Tahun Pelajaran 2025/2026',
        'content' => 'SMP Negeri 1 Kotabaru menyediakan informasi profil sekolah, agenda, dokumen, kegiatan kesiswaan, dan layanan PPDB melalui website resmi ini.',
        'link_label' => 'Lihat Kontak',
        'link_url' => '/kontak',
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => 1,
        'sort_order' => 1,
    ],
    [
        'title' => 'Penguatan Pembelajaran Mendalam',
        'content' => 'Sekolah mengembangkan pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, dan pengambilan keputusan berbasis data.',
        'link_label' => 'Lihat Akademik',
        'link_url' => '/akademik',
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => 1,
        'sort_order' => 2,
    ],
];
foreach ($announcements as $announcement) {
    seed_when_missing('announcements', 'title', $announcement['title'], static fn() => save_announcement(['id' => null] + $announcement), 'Pengumuman "' . $announcement['title'] . '"');
}

$newsItems = [
    [
        'slug' => 'program-pembelajaran-mendalam-smp-negeri-1-kotabaru',
        'title' => 'Program Pembelajaran Mendalam SMP Negeri 1 Kotabaru',
        'category' => 'Akademik',
        'excerpt' => 'Sekolah memperkuat kepemimpinan pembelajaran, budaya kolaboratif, dan transformasi manajemen sekolah melalui siklus inkuiri kolaboratif.',
        'content' => "SMP Negeri 1 Kotabaru melaksanakan Program Pembelajaran Mendalam Kepala Sekolah dengan pendekatan Siklus Inkuiri Kolaboratif.\n\nProgram ini mendorong eksplorasi praktik terbaik, refleksi kritis terhadap konteks lokal, dan pengambilan keputusan berbasis data yang melibatkan guru, tenaga kependidikan, peserta didik, komite sekolah, orang tua, dan mitra eksternal.\n\nArah program difokuskan pada penyelarasan visi, misi, dan tujuan sekolah, penguatan pembelajaran berbasis proyek, budaya lokal, teknologi, serta pengembangan komunitas belajar yang adaptif dan berkelanjutan.",
        'image_path' => $logo,
        'published_at' => '2025-10-12 08:00:00',
        'is_active' => 1,
    ],
    [
        'slug' => 'profil-data-sekolah-desember-2025',
        'title' => 'Profil Data Sekolah Desember 2025',
        'category' => 'Profil Sekolah',
        'excerpt' => 'Laporan bulanan mencatat SMP Negeri 1 Kotabaru memiliki 775 siswa dalam 24 rombongan belajar pada tahun pelajaran 2025/2026.',
        'content' => "Berdasarkan laporan bulanan sekolah bulan Desember 2025, SMP Negeri 1 Kotabaru menyelenggarakan pendidikan pada tahun pelajaran 2025/2026 dengan 775 peserta didik.\n\nJumlah tersebut terdiri dari 376 siswa laki-laki dan 399 siswa perempuan yang tersebar dalam 24 rombongan belajar.\n\nData ini menjadi bagian dari dasar pengelolaan layanan akademik, kesiswaan, sarana prasarana, serta perencanaan program sekolah.",
        'image_path' => $logo,
        'published_at' => '2025-12-31 08:00:00',
        'is_active' => 1,
    ],
];
foreach ($content['latestNews'] ?? [] as $item) {
    $newsItems[] = [
        'slug' => $item['slug'],
        'title' => $item['title'],
        'category' => $item['category'],
        'excerpt' => $item['desc'],
        'content' => implode("\n\n", $item['content']),
        'image_path' => $logo,
        'published_at' => date('Y-m-d H:i:s', strtotime($item['date'] ?? 'now')),
        'is_active' => 1,
    ];
}
foreach ($newsItems as $item) {
    seed_when_missing('news', 'slug', $item['slug'], static fn() => save_news(['id' => null] + $item), 'Berita "' . $item['title'] . '"');
}

$galleryItems = [
    ['title' => 'Kepala Sekolah', 'description' => 'Dokumentasi resmi kepala sekolah SMP Negeri 1 Kotabaru.', 'image_path' => $principalPhoto, 'sort_order' => 1],
    ['title' => 'Logo Sekolah', 'description' => 'Identitas visual resmi SMP Negeri 1 Kotabaru.', 'image_path' => $logo, 'sort_order' => 2],
];
foreach ($galleryItems as $item) {
    seed_when_missing('gallery_items', 'title', $item['title'], static fn() => save_gallery_item(['id' => null] + $item), 'Galeri "' . $item['title'] . '"');
}

$agendaItems = [
    ['date' => '2026-05-05 08:00:00', 'title' => 'Sosialisasi Program Semester Genap', 'detail' => 'Penyampaian agenda pembelajaran, asesmen, dan kegiatan sekolah kepada orang tua atau wali peserta didik.'],
    ['date' => '2026-05-11 08:00:00', 'title' => 'Pekan Literasi dan Numerasi', 'detail' => 'Pelaksanaan kegiatan kelas, penguatan budaya baca, dan proyek numerasi terintegrasi untuk seluruh tingkat.'],
    ['date' => '2026-05-18 08:00:00', 'title' => 'Seleksi Internal Lomba Siswa', 'detail' => 'Tahap persiapan peserta didik untuk mewakili sekolah dalam ajang akademik dan non-akademik tingkat kabupaten.'],
];
foreach ($agendaItems as $index => $item) {
    seed_when_missing('agenda_items', 'title', $item['title'], static fn() => save_agenda_item([
        'id' => null,
        'title' => $item['title'],
        'category' => 'Agenda Sekolah',
        'location' => 'SMP Negeri 1 Kotabaru',
        'description' => $item['detail'],
        'starts_at' => $item['date'],
        'ends_at' => null,
        'is_active' => 1,
        'academic_year' => current_academic_year(),
        'sort_order' => $index + 1,
    ]), 'Agenda "' . $item['title'] . '"');
}

$achievements = [
    [
        'title' => 'Akreditasi A SMP Negeri 1 Kotabaru',
        'level' => 'Nasional',
        'category' => 'Sekolah',
        'achiever_name' => 'SMP Negeri 1 Kotabaru',
        'achievement_date' => '2023-05-25',
        'description' => 'SMP Negeri 1 Kotabaru memperoleh Akreditasi A berdasarkan SK No. 555/BAN-SM/SK/2023 tanggal 25 Mei 2023.',
        'image_path' => $logo,
        'is_active' => 1,
        'academic_year' => $year,
        'sort_order' => 1,
    ],
    [
        'title' => 'Pengembangan Program Pembelajaran Mendalam',
        'level' => 'Sekolah',
        'category' => 'Akademik',
        'achiever_name' => 'Tim Perumus VMT dan Warga Sekolah',
        'achievement_date' => '2025-10-12',
        'description' => 'Sekolah menyusun dan menjalankan program pembelajaran mendalam berbasis siklus inkuiri kolaboratif sebagai fondasi peningkatan mutu pembelajaran.',
        'image_path' => $logo,
        'is_active' => 1,
        'academic_year' => $year,
        'sort_order' => 2,
    ],
];
foreach ($achievements as $achievement) {
    seed_when_missing('school_achievements', 'title', $achievement['title'], static fn() => save_achievement(['id' => null] + $achievement), 'Prestasi "' . $achievement['title'] . '"');
}

$extracurriculars = [
    ['name' => 'Pramuka', 'category' => 'Wajib', 'coach' => null, 'schedule_text' => null, 'location' => 'SMP Negeri 1 Kotabaru', 'description' => 'Pembinaan kedisiplinan, kemandirian, kerja sama, dan kepedulian sosial melalui kegiatan kepramukaan.', 'image_path' => $logo, 'is_active' => 1, 'sort_order' => 1],
    ['name' => 'PMR', 'category' => 'Kesehatan', 'coach' => null, 'schedule_text' => null, 'location' => 'SMP Negeri 1 Kotabaru', 'description' => 'Pembinaan kepedulian, kesiapsiagaan, dan layanan dasar kesehatan di lingkungan sekolah.', 'image_path' => $logo, 'is_active' => 1, 'sort_order' => 2],
    ['name' => 'Futsal', 'category' => 'Olahraga', 'coach' => null, 'schedule_text' => null, 'location' => 'Lapangan sekolah', 'description' => 'Kegiatan pengembangan kebugaran, sportivitas, dan kerja sama tim melalui pembinaan olahraga.', 'image_path' => $logo, 'is_active' => 1, 'sort_order' => 3],
    ['name' => 'Paduan Suara', 'category' => 'Seni', 'coach' => null, 'schedule_text' => null, 'location' => 'SMP Negeri 1 Kotabaru', 'description' => 'Pengembangan kepekaan seni, disiplin latihan, dan kerja sama melalui kegiatan vokal bersama.', 'image_path' => $logo, 'is_active' => 1, 'sort_order' => 4],
    ['name' => 'Seni Tari', 'category' => 'Seni', 'coach' => null, 'schedule_text' => null, 'location' => 'SMP Negeri 1 Kotabaru', 'description' => 'Pembinaan kreativitas, apresiasi budaya, dan keberanian tampil melalui kegiatan seni pertunjukan.', 'image_path' => $logo, 'is_active' => 1, 'sort_order' => 5],
];
foreach ($extracurriculars as $item) {
    seed_when_missing('extracurriculars', 'name', $item['name'], static fn() => save_extracurricular(['id' => null] + $item), 'Ekskul "' . $item['name'] . '"');
}

$faqs = [
    ['question' => 'Di mana alamat SMP Negeri 1 Kotabaru?', 'answer' => 'Alamat sekolah berada di Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117.', 'category' => 'Profil', 'is_active' => 1, 'sort_order' => 1],
    ['question' => 'Bagaimana cara menghubungi sekolah?', 'answer' => 'Sekolah dapat dihubungi melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan Senin-Jumat pukul 07.00-15.00.', 'category' => 'Layanan', 'is_active' => 1, 'sort_order' => 2],
    ['question' => 'Apakah informasi PPDB tersedia di website?', 'answer' => 'Ya. Informasi PPDB, persyaratan, dan catatan penting tersedia pada menu PPDB. Pendaftaran resmi ditangani Dinas Pendidikan, bukan website sekolah. Jadwal resmi mengikuti pengumuman sekolah dan dinas terkait.', 'category' => 'PPDB', 'is_active' => 1, 'sort_order' => 3],
    ['question' => 'Dokumen apa saja yang biasanya diperlukan untuk PPDB?', 'answer' => 'Calon peserta didik biasanya perlu menyiapkan kartu keluarga, akta kelahiran, dokumen identitas atau pendukung sesuai jalur pendaftaran, pas foto, dan dokumen tambahan sesuai pengumuman panitia.', 'category' => 'PPDB', 'is_active' => 1, 'sort_order' => 4],
    ['question' => 'Apakah sekolah memiliki kegiatan ekstrakurikuler?', 'answer' => 'Ya. Sekolah menyediakan kegiatan pengembangan minat dan bakat seperti Pramuka, Jurnalistik, PMR, Futsal, Paduan Suara, Seni Tari, dan kegiatan lain yang dapat diperbarui melalui menu Ekstrakurikuler.', 'category' => 'Kesiswaan', 'is_active' => 1, 'sort_order' => 5],
    ['question' => 'Bagaimana cara melihat agenda sekolah?', 'answer' => 'Agenda kegiatan sekolah dapat dilihat melalui menu Agenda. Admin sekolah dapat memperbarui jadwal kegiatan sesuai tahun ajaran aktif.', 'category' => 'Agenda', 'is_active' => 1, 'sort_order' => 6],
    ['question' => 'Apakah dokumen sekolah bisa diunduh?', 'answer' => 'Ya. Dokumen resmi seperti kalender akademik, formulir, surat edaran, tata tertib, dan file PDF lain dapat diunggah admin dan diunduh melalui menu Dokumen.', 'category' => 'Dokumen', 'is_active' => 1, 'sort_order' => 7],
    ['question' => 'Apa fokus pengembangan pembelajaran sekolah?', 'answer' => 'Sekolah memperkuat pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, teknologi, dan pengambilan keputusan berbasis data.', 'category' => 'Akademik', 'is_active' => 1, 'sort_order' => 8],
];
foreach ($faqs as $faq) {
    seed_when_missing('faqs', 'question', $faq['question'], static fn() => save_faq(['id' => null] + $faq), 'FAQ "' . $faq['question'] . '"');
}

$ppdbEntries = [];
foreach ($content['ppdbSteps'] ?? [] as $index => $step) {
    $ppdbEntries[] = ['section' => 'alur', 'title' => 'Langkah ' . ($index + 1), 'content' => $step, 'sort_order' => $index + 1];
}
foreach ($content['ppdbInformation'] ?? [] as $index => $item) {
    $ppdbEntries[] = ['section' => 'informasi', 'title' => $item['title'], 'content' => $item['desc'], 'sort_order' => $index + 1];
}
foreach ($content['ppdbRequirements'] ?? [] as $index => $requirement) {
    $ppdbEntries[] = ['section' => 'persyaratan', 'title' => 'Persyaratan ' . ($index + 1), 'content' => $requirement, 'sort_order' => $index + 1];
}
foreach ($content['ppdbNotes'] ?? [] as $index => $note) {
    $ppdbEntries[] = ['section' => 'catatan', 'title' => 'Catatan ' . ($index + 1), 'content' => $note, 'sort_order' => $index + 1];
}
foreach ($ppdbEntries as $entry) {
    seed_when_missing('ppdb_entries', 'section_title', $entry['section'] . '|' . $entry['title'], static fn() => save_ppdb_entry($entry), 'Konten PPDB "' . $entry['section'] . ' - ' . $entry['title'] . '"');
}

echo PHP_EOL . 'Catatan: modul Dokumen tetap perlu diisi dari admin dengan file PDF asli agar tautan unduh valid.' . PHP_EOL;
