<?php

declare(strict_types=1);

final class SiteSettingsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
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
                'details' => 'Halaman publik sedang ditutup sementara agar tim sekolah dapat melakukan pembaruan data, pengecekan keamanan, atau persiapan publikasi. Silakan akses kembali website ini beberapa saat lagi.',
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
                'principalSlogan' => 'Mewujudkan generasi yang unggul dalam prestasi, karakter, dan pemanfaatan teknologi.',
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

        $changed = 0;
        foreach ($settings as $key => $value) {
            $changed += (int) $this->saveSetting($pdo, $key, $value);
        }
        return $changed;
    }

    private function saveSetting(PDO $pdo, string $key, array $value): bool
    {
        $query = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
        $query->execute([$key]);
        $stored = $query->fetchColumn();
        if ($stored === false) {
            return $this->insertMissing($pdo, 'site_settings', [
                'setting_key' => $key,
                'setting_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ], ['setting_key']);
        }
        $current = json_decode($stored, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($current) || array_is_list($value) || array_is_list($current)) {
            return false;
        }
        $merged = $current + $value;
        if ($merged === $current) {
            return false;
        }
        $update = $pdo->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
        $update->execute([json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), $key]);
        return true;
    }
}
