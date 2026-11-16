<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
if (count($argv) !== 2 || $argv[1] !== '--seed') {
    fwrite(STDERR, "Use: php database/seed_final_content.php --seed\n");
    exit(1);
}

require_once __DIR__ . '/../app/core/database.php';

// Initial text content only. Existing records are preserved; media are uploaded via admin.

$settings = [
    'schoolProfile' => [
        'name' => 'SMP Negeri 1 Kotabaru',
        'shortName' => 'SMPN 1 Kotabaru',
        'tagline' => 'Sekolah Aktif, Berkarakter, dan Berprestasi',
        'description' => 'Website resmi sekolah sebagai sarana publikasi, layanan informasi, dan komunikasi kelembagaan yang mendukung transformasi pembelajaran, budaya kolaboratif, serta peningkatan mutu pendidikan.',
        'heroDescription' => 'Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.',
        'address' => 'Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117',
        'phone' => '(0518) 21602',
        'email' => 'smpn1_54ktb@yahoo.co.id',
        'serviceHours' => 'Senin-Jumat, 07.00-15.00',
    ],
    'siteMaintenance' => [
        'enabled' => false,
        'message' => 'Website sekolah sedang dalam pemeliharaan singkat. Silakan kembali beberapa saat lagi.',
    ],
    'siteTheme' => [
        'primaryColor' => '#0284c7',
        'primaryDarkColor' => '#0b4fa8',
        'secondaryColor' => '#bef4fe',
        'accentColor' => '#eab308',
        'darkColor' => '#0f172a',
    ],
    'siteAcademic' => [
        'currentYear' => '2026/2027',
    ],
    'siteHome' => [
        'heroTitle' => 'SMP Negeri 1 Kotabaru',
        'heroSubtitle' => 'Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.',
        'primaryButtonLabel' => 'Lihat Profil Sekolah',
        'primaryButtonUrl' => '/profil',
        'secondaryButtonLabel' => 'Informasi PPDB',
        'secondaryButtonUrl' => '/ppdb',
        'profileVideoTitle' => 'Profil sekolah dalam satu tayangan',
        'profileVideoDescription' => 'Ruang ini disiapkan untuk menampilkan video profil resmi sekolah, mulai dari lingkungan belajar, kegiatan siswa, fasilitas, hingga budaya sekolah.',
        'teacherLimit' => 12,
        'agendaLimit' => 3,
        'showAnnouncements' => true,
        'showQuickLinks' => true,
        'showLogoCard' => true,
        'showStats' => true,
        'showPrincipalCard' => true,
        'showTeacherCarousel' => true,
        'showProfileIntro' => true,
        'showFeaturedAreas' => true,
        'showAgenda' => true,
    ],
    'navigation' => [
        [
            'href' => '/',
            'label' => 'Beranda',
            'visible' => true,
        ],
        [
            'href' => '/profil',
            'label' => 'Profil',
            'visible' => false,
        ],
        [
            'href' => '/fasilitas',
            'label' => 'Fasilitas',
            'visible' => false,
        ],
        [
            'href' => '/akademik',
            'label' => 'Akademik',
            'visible' => false,
        ],
        [
            'href' => '/agenda',
            'label' => 'Agenda',
            'visible' => true,
        ],
        [
            'href' => '/guru-staf',
            'label' => 'Guru & Staf',
            'visible' => true,
        ],
        [
            'href' => '/dokumen',
            'label' => 'Dokumen',
            'visible' => false,
        ],
        [
            'href' => '/prestasi',
            'label' => 'Prestasi',
            'visible' => true,
        ],
        [
            'href' => '/kesiswaan',
            'label' => 'Kesiswaan',
            'visible' => false,
        ],
        [
            'href' => '/ekstrakurikuler',
            'label' => 'Ekskul',
            'visible' => true,
        ],
        [
            'href' => '/berita',
            'label' => 'Berita',
            'visible' => true,
        ],
        [
            'href' => '/galeri',
            'label' => 'Galeri',
            'visible' => false,
        ],
        [
            'href' => '/ppdb',
            'label' => 'PPDB',
            'visible' => false,
        ],
        [
            'href' => '/faq',
            'label' => 'FAQ',
            'visible' => false,
        ],
        [
            'href' => '/kontak',
            'label' => 'Kontak',
            'visible' => false,
        ],
    ],
    'stats' => [
        [
            'label' => 'Siswa',
            'value' => '775',
        ],
        [
            'label' => 'Guru & Staf',
            'value' => '65',
        ],
        [
            'label' => 'Ekstrakurikuler',
            'value' => '19',
        ],
        [
            'label' => 'Rombel',
            'value' => '24',
        ],
    ],
    'quickLinks' => [
        [
            'title' => 'Profil Sekolah',
            'desc' => 'Sejarah singkat, visi, misi, dan gambaran umum penyelenggaraan pendidikan di sekolah.',
            'href' => '/profil',
        ],
        [
            'title' => 'Fasilitas Sekolah',
            'desc' => 'Ruang belajar, laboratorium, perpustakaan, lapangan, dan sarana pendukung kegiatan sekolah.',
            'href' => '/fasilitas',
        ],
        [
            'title' => 'Agenda Akademik',
            'desc' => 'Kalender pendidikan, asesmen, dan jadwal kegiatan akademik penting.',
            'href' => '/akademik',
        ],
        [
            'title' => 'Ekstrakurikuler',
            'desc' => 'Program pengembangan minat, bakat, dan pembinaan karakter peserta didik.',
            'href' => '/ekstrakurikuler',
        ],
        [
            'title' => 'Informasi PPDB',
            'desc' => 'Alur, persyaratan, dan jadwal penerimaan peserta didik baru.',
            'href' => '/ppdb',
        ],
    ],
    'featuredAreas' => [
        [
            'title' => 'Akademik',
            'desc' => 'Informasi pembelajaran, asesmen, agenda akademik, dan layanan pendidikan sekolah.',
            'href' => '/akademik',
        ],
        [
            'title' => 'Kesiswaan',
            'desc' => 'Informasi organisasi siswa, ekstrakurikuler, pembinaan karakter, dan prestasi peserta didik.',
            'href' => '/kesiswaan',
        ],
        [
            'title' => 'Publikasi',
            'desc' => 'Berita sekolah, dokumentasi kegiatan, galeri, dan informasi publik resmi sekolah.',
            'href' => '/berita',
        ],
    ],
    'excellencePrograms' => [
        'Pelaksanaan pembelajaran berbasis eksplorasi, refleksi, proyek, dan kolaborasi.',
        'Penguatan budaya inkuiri kolaboratif melalui keterlibatan guru, peserta didik, orang tua, komite, dan mitra sekolah.',
        'Pengembangan pembelajaran berbasis budaya lokal, teknologi, dan konteks kehidupan nyata peserta didik.',
        'Penguatan komunitas belajar guru melalui forum diskusi, refleksi praktik, dan berbagi praktik baik.',
    ],
    'schoolHighlights' => [
        [
            'title' => 'Kepemimpinan Pembelajaran',
            'desc' => 'Sekolah memperkuat peran kepala sekolah sebagai pemimpin pembelajaran, fasilitator perubahan pedagogis, dan penggerak budaya kolaboratif.',
        ],
        [
            'title' => 'Pembelajaran Mendalam',
            'desc' => 'Pembelajaran diarahkan pada pemahaman bermakna, eksplorasi lintas disiplin, refleksi, kolaborasi, dan pengembangan karakter pembelajar sepanjang hayat.',
        ],
        [
            'title' => 'Budaya Reflektif',
            'desc' => 'Warga sekolah dilibatkan dalam proses inkuiri, pengambilan keputusan berbasis data, dokumentasi praktik baik, dan perbaikan berkelanjutan.',
        ],
    ],
    'profileSections' => [
        [
            'title' => 'Sejarah Singkat',
            'text' => 'SMP Negeri 1 Kotabaru merupakan institusi pendidikan yang berkomitmen terhadap transformasi mutu pembelajaran melalui penguatan kepemimpinan pembelajaran, budaya kolaboratif, dan pengambilan keputusan berbasis data. Sekolah mendorong proses pendidikan yang adaptif, inklusif, reflektif, serta relevan dengan kebutuhan peserta didik dan tantangan abad ke-21.',
        ],
        [
            'title' => 'Visi',
            'text' => 'Mewujudkan peserta didik yang beriman dan berakhlak mulia, berpikir kritis, kreatif, dan reflektif, serta mampu berinovasi dan berkolaborasi dalam teknologi, berakar pada budaya lokal, dan berorientasi pada keberlanjutan.',
        ],
        [
            'title' => 'Misi',
            'text' => '1. Merancang pembelajaran berbasis eksplorasi, refleksi, dan kolaborasi. 2. Mengintegrasikan pembelajaran berbasis proyek dan budaya lokal. 3. Meningkatkan manajemen pendidikan yang adaptif dan ramah anak melalui kebijakan pembelajaran yang mendorong otonomi profesional guru serta partisipasi aktif murid. 4. Merancang strategi pembelajaran diferensiatif dan berbasis kekuatan murid. 5. Menjalin kemitraan pembelajaran berbasis komunitas dan CSR. 6. Menyusun kurikulum yang menumbuhkan pemikiran reflektif dan inovatif. 7. Mendorong guru sebagai fasilitator pembelajaran mendalam melalui refleksi dan praktik inovatif. 8. Menjadikan sekolah sebagai komunitas pembelajar yang adaptif dan berkelanjutan.',
        ],
    ],
    'schoolIdentity' => [
        [
            'label' => 'Nama Sekolah',
            'value' => 'SMP Negeri 1 Kotabaru',
        ],
        [
            'label' => 'Status',
            'value' => 'Sekolah Menengah Pertama Negeri',
        ],
        [
            'label' => 'NPSN',
            'value' => '30303355',
        ],
        [
            'label' => 'NSS',
            'value' => '201150901001',
        ],
        [
            'label' => 'Alamat',
            'value' => 'Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117',
        ],
        [
            'label' => 'Akreditasi',
            'value' => 'A - SK No. 555/BAN-SM/SK/2023 tanggal 25 Mei 2023',
        ],
        [
            'label' => 'Tahun Berdiri',
            'value' => '1954',
        ],
        [
            'label' => 'Jam Layanan',
            'value' => 'Senin-Jumat, 07.00-15.00',
        ],
        [
            'label' => 'Email Resmi',
            'value' => 'smpn1_54ktb@yahoo.co.id',
        ],
        [
            'label' => 'Telepon',
            'value' => '(0518) 21602',
        ],
    ],
    'schoolObjectives' => [
        'Dalam 1 tahun, 100% guru menerapkan minimal dua model pembelajaran mendalam berbasis proyek.',
        'Dalam 1 tahun, 80% peserta didik menghasilkan karya berbasis budaya lokal dalam kegiatan P5.',
        'Dalam 4 tahun, 90% lulusan menunjukkan capaian minimal lima dimensi Profil Pelajar Pancasila.',
        'Dalam 2 tahun, seluruh kelas menerapkan pembelajaran berbasis refleksi dan kolaborasi.',
        'Dalam 1 tahun, minimal tiga kegiatan pembelajaran melibatkan orang tua dan mitra komunitas.',
    ],
    'academicPrograms' => [
        [
            'title' => 'Pembelajaran Mendalam',
            'desc' => 'Pembelajaran dirancang berbasis eksplorasi, refleksi, kolaborasi, proyek, dan pemecahan masalah agar peserta didik membangun pemahaman yang bermakna.',
        ],
        [
            'title' => 'Kurikulum Berbasis Proyek dan Konteks',
            'desc' => 'Program pembelajaran mengintegrasikan proyek otentik, budaya lokal, Profil Pelajar Pancasila, serta konteks kehidupan nyata peserta didik.',
        ],
        [
            'title' => 'Komunitas Belajar Guru',
            'desc' => 'Guru difasilitasi melalui In House Training, forum diskusi rutin, coaching reflektif, dan berbagi praktik baik dalam komunitas belajar sekolah.',
        ],
    ],
    'academicServices' => [
        [
            'title' => 'Asesmen Formatif Berkelanjutan',
            'desc' => 'Asesmen dilakukan untuk memberi umpan balik reflektif, memantau perkembangan pemahaman, dan mendorong perbaikan pembelajaran.',
        ],
        [
            'title' => 'Asesmen Kinerja Otentik',
            'desc' => 'Penilaian dilakukan melalui portofolio, proyek, presentasi, dan karya peserta didik yang menunjukkan pemahaman serta keterampilan.',
        ],
        [
            'title' => 'Pendampingan dan Coaching Guru',
            'desc' => 'Kepala sekolah, wakil kepala kurikulum, dan guru fasilitator mendampingi implementasi pembelajaran mendalam di kelas.',
        ],
        [
            'title' => 'Refleksi dan Perbaikan Berkelanjutan',
            'desc' => 'Sekolah menggunakan survei, observasi kelas, analisis perangkat ajar, jurnal refleksi, dan diskusi kelompok untuk merumuskan perbaikan.',
        ],
    ],
    'academicSupportItems' => [
        'Sosialisasi VMT dan pembelajaran mendalam kepada warga sekolah',
        'Komunitas Belajar Guru dan forum Sabtu Berbagi',
        'RPP pembelajaran mendalam berbasis proyek dan refleksi',
        'Observasi kelas dan coaching reflektif',
        'Bank data digital praktik baik guru',
        'Keterlibatan orang tua, komite, komunitas, CSR, dan mitra eksternal',
    ],
    'academicCommitments' => [
        'Menyelenggarakan pembelajaran yang eksploratif, reflektif, kolaboratif, dan berbasis proyek.',
        'Menguatkan budaya inkuiri kolaboratif dan pengambilan keputusan berbasis data.',
        'Mengembangkan sekolah sebagai komunitas pembelajar yang adaptif, inklusif, dan berkelanjutan.',
    ],
    'studentPrograms' => [
        [
            'title' => 'OSIS dan Kepemimpinan',
            'desc' => 'Program pembinaan organisasi siswa, kepemimpinan, dan budaya partisipatif yang mendukung tumbuhnya tanggung jawab serta kemandirian.',
        ],
        [
            'title' => 'Ekstrakurikuler',
            'desc' => 'Pilihan kegiatan seperti pramuka, jurnalistik, olahraga, seni, dan program lain untuk mendukung pengembangan minat serta bakat peserta didik.',
        ],
        [
            'title' => 'Pembinaan Karakter',
            'desc' => 'Kegiatan disiplin, literasi, gotong royong, dan pembiasaan positif yang dilaksanakan secara konsisten dalam kehidupan sekolah sehari-hari.',
        ],
    ],
    'studentDevelopmentAreas' => [
        [
            'title' => 'Pembinaan Organisasi',
            'desc' => 'Pendidikan kepemimpinan, tanggung jawab, dan partisipasi aktif melalui OSIS serta kegiatan kesiswaan lainnya.',
        ],
        [
            'title' => 'Penguatan Karakter',
            'desc' => 'Pembiasaan disiplin, kepedulian sosial, gotong royong, dan budaya santun dalam kehidupan sekolah sehari-hari.',
        ],
        [
            'title' => 'Pengembangan Minat dan Bakat',
            'desc' => 'Fasilitasi kegiatan ekstrakurikuler sebagai wadah penyaluran potensi siswa di bidang akademik, olahraga, seni, dan organisasi.',
        ],
        [
            'title' => 'Pembinaan Prestasi',
            'desc' => 'Pendampingan siswa dalam mengikuti seleksi, lomba, dan kegiatan representatif di tingkat sekolah maupun luar sekolah.',
        ],
    ],
    'studentGuidancePoints' => [
        'Pembinaan tata tertib dan budaya disiplin sekolah',
        'Pendampingan kegiatan OSIS dan kepemimpinan siswa',
        'Pelaksanaan kegiatan ekstrakurikuler secara terarah dan berkelanjutan',
        'Pencatatan serta pembinaan prestasi akademik maupun non-akademik',
        'Koordinasi dengan wali kelas dan orang tua dalam pembinaan peserta didik',
    ],
    'galleryHighlights' => [
        'Upacara, kegiatan rutin, dan agenda resmi sekolah',
        'Kegiatan pembelajaran di kelas, perpustakaan, dan laboratorium',
        'Ekstrakurikuler, pembinaan prestasi, dan lomba peserta didik',
        'Dokumentasi video profil, publikasi, dan kegiatan representatif sekolah',
    ],
    'contactChannels' => [
        [
            'title' => 'Alamat Sekolah',
            'value' => 'Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117',
            'desc' => 'Alamat resmi sekolah sesuai laporan bulanan sekolah.',
        ],
        [
            'title' => 'Telepon',
            'value' => '(0518) 21602',
            'desc' => 'Nomor telepon resmi sekolah yang dapat dihubungi pada jam layanan administrasi.',
        ],
        [
            'title' => 'Email Resmi',
            'value' => 'smpn1_54ktb@yahoo.co.id',
            'desc' => 'Alamat surat elektronik resmi sekolah untuk layanan informasi dan korespondensi.',
        ],
        [
            'title' => 'Jam Layanan',
            'value' => 'Senin-Jumat, 07.00-15.00',
            'desc' => 'Jam layanan sekolah untuk administrasi, komunikasi orang tua, dan keperluan informasi umum.',
        ],
    ],
    'contactServices' => [
        'Layanan informasi umum sekolah',
        'Layanan administrasi peserta didik dan orang tua',
        'Layanan komunikasi terkait akademik dan kesiswaan',
        'Layanan informasi PPDB dan kegiatan sekolah',
    ],
    'contactNotes' => [
        'Pengunjung diharapkan menggunakan saluran komunikasi resmi sekolah untuk memperoleh informasi yang akurat.',
        'Pertanyaan yang berkaitan dengan peserta didik dapat disampaikan pada jam layanan agar proses tindak lanjut lebih terarah.',
        'Informasi website dan media sosial resmi dapat dilengkapi kembali setelah kanal resmi sekolah tersedia.',
    ],
    'homeProfileIntro' => [
        'title' => 'Ruang digital resmi untuk menampilkan identitas dan arah sekolah',
        'description' => 'Website ini menjadi pintu masuk utama menuju halaman profil, akademik, kesiswaan, berita, galeri, PPDB, dan kontak.',
        'heading' => 'Sekolah yang mendukung pembelajaran, karakter, dan kreativitas siswa',
        'text' => 'SMP Negeri 1 Kotabaru berkomitmen menyelenggarakan layanan pendidikan yang mendorong perkembangan akademik, kedisiplinan, kepemimpinan, serta penguatan potensi non-akademik peserta didik.',
    ],
    'pageIntroductions' => [
        'accreditation_view' => 'Dokumen ini ditampilkan sebagai informasi resmi untuk pengunjung website.',
        'dokumen' => 'Unduh kalender akademik, formulir, surat edaran, tata tertib, dan dokumen resmi sekolah dalam format PDF.',
        'kontak' => 'Halaman kontak disediakan untuk memudahkan peserta didik, orang tua, dan masyarakat memperoleh akses ke alamat, nomor resmi, email, serta layanan informasi sekolah.',
        'berita' => 'Pusat publikasi sekolah untuk menyampaikan berita, pengumuman, dan dokumentasi kegiatan secara resmi kepada warga sekolah dan masyarakat.',
        'profil' => 'Halaman ini memuat gambaran umum sekolah, sejarah singkat, visi dan misi, sambutan kepala sekolah, identitas kelembagaan, serta arah pengembangan sekolah.',
        'galeri' => 'Halaman galeri menampilkan dokumentasi visual kegiatan sekolah sebagai bagian dari publikasi resmi lembaga.',
        'kesiswaan' => 'Informasi mengenai organisasi siswa, kegiatan ekstrakurikuler, pembinaan karakter, dan pengembangan potensi peserta didik sebagai bagian dari budaya sekolah.',
        'prestasi' => 'Dokumentasi capaian peserta didik, guru, dan sekolah dalam bidang akademik maupun non-akademik.',
        'fasilitas' => 'Informasi sarana dan prasarana sekolah yang mendukung proses belajar, kegiatan siswa, dan layanan pendidikan, dilengkapi foto agar kondisi fasilitas lebih mudah dilihat.',
        'kebijakan_privasi' => 'Penjelasan penggunaan data pribadi pada layanan informasi dan komunikasi website sekolah.',
        'ekstrakurikuler' => 'Wadah pengembangan minat, bakat, kepemimpinan, sportivitas, kreativitas, dan karakter peserta didik.',
        'faq' => 'Temukan jawaban singkat mengenai PPDB, administrasi, akademik, layanan sekolah, dan informasi umum lainnya.',
        'guru_staf' => 'Informasi pendidik dan tenaga kependidikan sekolah sebagai bagian dari keterbukaan layanan pendidikan kepada peserta didik, orang tua, dan masyarakat.',
        'akademik' => 'Informasi mengenai kurikulum, pembelajaran, asesmen, kalender pendidikan, serta layanan pendampingan akademik bagi peserta didik dan orang tua.',
        'cari' => 'Temukan berita, prestasi, agenda, dokumen, fasilitas, ekstrakurikuler, dan FAQ dari satu halaman.',
    ],
    'privacySections' => [
        [
            'title' => 'Penggunaan Data',
            'paragraphs' => [
                'Data yang dikirim melalui website sekolah digunakan untuk layanan informasi, komunikasi resmi sekolah, dan tindak lanjut layanan pendidikan.',
                'Halaman PPDB/SPMB hanya menyediakan informasi. Website ini tidak menerima pendaftaran maupun unggahan berkas calon siswa; pendaftaran resmi ditangani Dinas Pendidikan.',
            ],
        ],
        [
            'title' => 'Keamanan dan Akses',
            'paragraphs' => [
                'Akses administratif dibatasi melalui login admin dan hak akses sesuai tugas pengelola.',
                'Sekolah tidak membagikan data pribadi kepada pihak lain di luar kebutuhan administrasi sekolah, kecuali diwajibkan oleh ketentuan resmi yang berlaku.',
            ],
        ],
        [
            'title' => 'Pertanyaan Privasi',
            'paragraphs' => [
                'Untuk koreksi data atau pertanyaan terkait privasi, orang tua/wali dapat menghubungi sekolah melalui halaman kontak resmi.',
            ],
        ],
    ],
];

$facilities = [
    [
        'name' => '24 ruang teori/kelas',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => '24 ruang teori/kelas',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 1,
    ],
    [
        'name' => 'Laboratorium IPA',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Laboratorium IPA',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 2,
    ],
    [
        'name' => 'Laboratorium Fisika',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Laboratorium Fisika',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 3,
    ],
    [
        'name' => '2 laboratorium bahasa',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => '2 laboratorium bahasa',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 4,
    ],
    [
        'name' => 'Laboratorium komputer',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Laboratorium komputer',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 5,
    ],
    [
        'name' => 'Ruang perpustakaan',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang perpustakaan',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 6,
    ],
    [
        'name' => 'Ruang keterampilan',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang keterampilan',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 7,
    ],
    [
        'name' => 'Ruang serba guna',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang serba guna',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 8,
    ],
    [
        'name' => 'Ruang UKS',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang UKS',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 9,
    ],
    [
        'name' => 'Ruang BP/BK',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang BP/BK',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 10,
    ],
    [
        'name' => 'Ruang guru',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang guru',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 11,
    ],
    [
        'name' => 'Ruang OSIS',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang OSIS',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 12,
    ],
    [
        'name' => 'Kamar mandi/WC guru dan siswa',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Kamar mandi/WC guru dan siswa',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 13,
    ],
    [
        'name' => 'Gudang',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Gudang',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 14,
    ],
    [
        'name' => 'Ruang ibadah',
        'category' => 'Fasilitas',
        'location' => null,
        'capacity' => null,
        'condition_status' => 'Baik',
        'description' => 'Ruang ibadah',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 15,
    ],
];

$extracurriculars = [
    [
        'name' => 'Pramuka',
        'category' => 'Wajib',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Pembinaan kedisiplinan, kemandirian, kerja sama, dan kepedulian sosial melalui kegiatan kepramukaan.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 1,
    ],
    [
        'name' => 'Jurnalistik',
        'category' => 'Literasi',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Wadah pengembangan kemampuan menulis, wawancara, dokumentasi, dan publikasi kegiatan sekolah.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 2,
    ],
    [
        'name' => 'PMR',
        'category' => 'Kesehatan',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Pembinaan kepedulian, kesiapsiagaan, dan layanan dasar kesehatan di lingkungan sekolah.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 3,
    ],
    [
        'name' => 'Futsal',
        'category' => 'Olahraga',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Kegiatan pengembangan kebugaran, sportivitas, dan kerja sama tim melalui pembinaan olahraga.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 4,
    ],
    [
        'name' => 'Paduan Suara',
        'category' => 'Seni',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Pengembangan kepekaan seni, disiplin latihan, dan kerja sama melalui kegiatan vokal bersama.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 5,
    ],
    [
        'name' => 'Seni Tari',
        'category' => 'Seni',
        'coach' => null,
        'schedule_text' => null,
        'location' => null,
        'description' => 'Pembinaan kreativitas, apresiasi budaya, dan keberanian tampil melalui kegiatan seni pertunjukan.',
        'image_path' => '',
        'is_active' => 1,
        'sort_order' => 6,
    ],
];

$ppdbEntries = [
    [
        'section' => 'alur',
        'title' => 'Langkah 1',
        'content' => 'Membaca informasi umum, persyaratan, dan jadwal pendaftaran peserta didik baru.',
        'sort_order' => 1,
    ],
    [
        'section' => 'alur',
        'title' => 'Langkah 2',
        'content' => 'Menyiapkan dokumen yang dipersyaratkan sesuai ketentuan resmi Dinas Pendidikan.',
        'sort_order' => 2,
    ],
    [
        'section' => 'alur',
        'title' => 'Langkah 3',
        'content' => 'Melakukan pendaftaran melalui sistem resmi Dinas Pendidikan, bukan website sekolah.',
        'sort_order' => 3,
    ],
    [
        'section' => 'alur',
        'title' => 'Langkah 4',
        'content' => 'Mengikuti proses verifikasi berkas serta menunggu pengumuman hasil seleksi sesuai jadwal resmi.',
        'sort_order' => 4,
    ],
    [
        'section' => 'persyaratan',
        'title' => 'Persyaratan 1',
        'content' => 'Fotokopi kartu keluarga atau dokumen identitas keluarga yang masih berlaku',
        'sort_order' => 1,
    ],
    [
        'section' => 'persyaratan',
        'title' => 'Persyaratan 2',
        'content' => 'Fotokopi akta kelahiran calon peserta didik',
        'sort_order' => 2,
    ],
    [
        'section' => 'persyaratan',
        'title' => 'Persyaratan 3',
        'content' => 'Fotokopi kartu identitas atau dokumen pendukung sesuai ketentuan jalur pendaftaran',
        'sort_order' => 3,
    ],
    [
        'section' => 'persyaratan',
        'title' => 'Persyaratan 4',
        'content' => 'Pas foto terbaru sesuai kebutuhan administrasi sekolah',
        'sort_order' => 4,
    ],
    [
        'section' => 'persyaratan',
        'title' => 'Persyaratan 5',
        'content' => 'Dokumen tambahan lain sesuai pengumuman resmi panitia PPDB sekolah',
        'sort_order' => 5,
    ],
    [
        'section' => 'informasi',
        'title' => 'Jalur Pendaftaran',
        'content' => 'Informasi jalur pendaftaran mengikuti pengumuman resmi PPDB tahun pelajaran berjalan dari sekolah dan dinas terkait.',
        'sort_order' => 1,
    ],
    [
        'section' => 'informasi',
        'title' => 'Jadwal Pelaksanaan',
        'content' => 'Jadwal pendaftaran, verifikasi berkas, pengumuman hasil seleksi, dan daftar ulang akan dipublikasikan melalui kanal resmi sekolah.',
        'sort_order' => 2,
    ],
    [
        'section' => 'informasi',
        'title' => 'Layanan Panitia',
        'content' => 'Calon peserta didik dan orang tua dapat menghubungi sekolah melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan.',
        'sort_order' => 3,
    ],
    [
        'section' => 'kriteria',
        'title' => 'Zonasi',
        'content' => 'Jalur untuk calon peserta didik yang berdomisili dalam wilayah zonasi sekolah sesuai ketentuan PPDB yang berlaku.',
        'sort_order' => 1,
    ],
    [
        'section' => 'kriteria',
        'title' => 'Prestasi',
        'content' => 'Jalur untuk calon peserta didik yang memiliki prestasi akademik atau non-akademik sesuai bukti dan ketentuan panitia PPDB.',
        'sort_order' => 2,
    ],
    [
        'section' => 'kriteria',
        'title' => 'Afirmasi',
        'content' => 'Jalur untuk calon peserta didik dari keluarga kurang mampu atau kelompok sasaran afirmasi sesuai dokumen pendukung resmi.',
        'sort_order' => 3,
    ],
    [
        'section' => 'kriteria',
        'title' => 'Mutasi',
        'content' => 'Jalur perpindahan tugas orang tua/wali atau perpindahan peserta didik sesuai ketentuan dan dokumen pendukung.',
        'sort_order' => 4,
    ],
    [
        'section' => 'catatan',
        'title' => 'Catatan 1',
        'content' => 'Pastikan data calon peserta didik diisi sesuai dokumen resmi keluarga dan sekolah asal.',
        'sort_order' => 1,
    ],
    [
        'section' => 'catatan',
        'title' => 'Catatan 2',
        'content' => 'Berkas pendaftaran diverifikasi oleh panitia PPDB sesuai ketentuan yang berlaku.',
        'sort_order' => 2,
    ],
    [
        'section' => 'catatan',
        'title' => 'Catatan 3',
        'content' => 'Perubahan jadwal atau ketentuan PPDB akan diinformasikan melalui media resmi sekolah.',
        'sort_order' => 3,
    ],
];

$agendaItems = [
    [
        'title' => 'Sosialisasi Program Semester Genap',
        'category' => 'Agenda Sekolah',
        'location' => '',
        'description' => 'Penyampaian agenda pembelajaran, asesmen, dan kegiatan sekolah kepada orang tua atau wali peserta didik.',
        'starts_at' => '2026-05-05 08:00:00',
        'ends_at' => null,
        'is_active' => 1,
        'academic_year' => '2026/2027',
        'sort_order' => 1,
    ],
    [
        'title' => 'Pekan Literasi dan Numerasi',
        'category' => 'Agenda Sekolah',
        'location' => '',
        'description' => 'Pelaksanaan kegiatan kelas, penguatan budaya baca, dan proyek numerasi terintegrasi untuk seluruh tingkat.',
        'starts_at' => '2026-05-11 08:00:00',
        'ends_at' => null,
        'is_active' => 1,
        'academic_year' => '2026/2027',
        'sort_order' => 2,
    ],
    [
        'title' => 'Seleksi Internal Lomba Siswa',
        'category' => 'Agenda Sekolah',
        'location' => '',
        'description' => 'Tahap persiapan peserta didik untuk mewakili sekolah dalam ajang akademik dan non-akademik tingkat kabupaten.',
        'starts_at' => '2026-05-18 08:00:00',
        'ends_at' => null,
        'is_active' => 1,
        'academic_year' => '2026/2027',
        'sort_order' => 3,
    ],
];

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

function seed_insert(PDO $pdo, string $table, array $data, array $identity): bool
{
    $where = implode(' AND ', array_map(static fn(string $column): string => "`$column` = ?", $identity));
    $check = $pdo->prepare("SELECT 1 FROM `$table` WHERE $where LIMIT 1");
    $check->execute(array_map(static fn(string $column): mixed => $data[$column], $identity));
    if ($check->fetchColumn() !== false) {
        return false;
    }
    $columns = '`' . implode('`, `', array_keys($data)) . '`';
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $statement = $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
    $statement->execute(array_values($data));
    return true;
}

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "Database belum terhubung. Periksa .env.\n");
    exit(1);
}

try {
    $pdo->beginTransaction();
    $inserted = 0;
    foreach ($settings as $key => $value) {
        $inserted += (int) seed_insert($pdo, 'site_settings', [
            'setting_key' => $key,
            'setting_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        ], ['setting_key']);
    }
    $datasets = [
        ['school_facilities', $facilities, ['name']],
        ['extracurriculars', $extracurriculars, ['name']],
        ['ppdb_entries', $ppdbEntries, ['section', 'title']],
        ['agenda_items', $agendaItems, ['title']],
        ['announcements', $announcements, ['title']],
        ['faqs', $faqs, ['question']],
    ];
    foreach ($datasets as [$table, $rows, $identity]) {
        foreach ($rows as $row) {
            $inserted += (int) seed_insert($pdo, $table, $row, $identity);
        }
    }
    $pdo->commit();
    echo "Seed selesai: $inserted data baru ditambahkan.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed gagal. Periksa koneksi dan struktur database; perubahan dibatalkan.\n");
    exit(1);
}
