-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 15 Nov 2026 pada 10.17
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `website_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `role` enum('superadmin','editor','ppdb') NOT NULL DEFAULT 'editor',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `username`, `full_name`, `role`, `is_active`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'Administrator', 'superadmin', 1, '$2y$10$X5aDPnp61PIhz6UOTw8XVO7ErygP4tO50EgSGwBj1dttSEBo1kbb6', '2026-04-29 02:07:14'),
(4, 'AdminTU', 'Muhammad Rifani', 'editor', 1, '$2y$10$KBY5XE4oQ6dCweUFGTnK.ek3nU7tcOk6spC8yL3RwPR2hS42ML6B6', '2026-04-29 11:19:25'),
(17, 'hendri', 'Hendri Arifin', 'editor', 1, '$2y$10$rx6bSDcGG9XqaC3J.O3FcuJ3uHptnGCd/EgibcGF/T56LiaPkWxSu', '2026-05-01 01:36:22'),
(19, 'Yulianoor', 'Yulianoor', 'editor', 1, '$2y$10$TxMnaulE1Jc0T69D6PCCS.rpqiWtt/Ux3WLRA7FRuHaP3SlmMy48y', '2026-05-01 04:10:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `agenda_items`
--

CREATE TABLE `agenda_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(120) NOT NULL,
  `location` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `agenda_items`
--

INSERT INTO `agenda_items` (`id`, `title`, `category`, `location`, `description`, `starts_at`, `ends_at`, `is_active`, `sort_order`, `created_at`, `academic_year`) VALUES
(2, '71th SMP Negeri 1 Kotabaru', 'Peringatan', 'Aula', 'Tepat pada tanggal 21 Agustus 1954, sebuah tonggak sejarah pendidikan berdiri di bumi Kotabaru. SMP Negeri 1 Kotabaru resmi memulai perjalanannya untuk mencerdaskan putra-putri daerah. Sejak saat itu, sekolah ini bukan sekadar gedung tempat belajar, melainkan sebuah kawah candradimuka yang telah melahirkan ribuan alumni yang berkontribusi bagi bangsa.\r\n\r\nMelewati berbagai lintasan zaman, SMP Negeri 1 Kotabaru tetap teguh memegang komitmennya. Berakar pada nilai sejarah yang kuat, kami terus bertransformasi menjadi institusi yang modern namun tetap menjaga keluhuran budi pekerti. Perjalanan dari tahun 1954 hingga hari ini adalah bukti resiliensi dan dedikasi seluruh warga sekolah dalam mewujudkan pendidikan yang berkualitas, inklusif, dan berorientasi pada masa depan.', '2026-08-21 09:49:00', NULL, 1, 1, '2026-04-30 01:51:19', '2025/2026'),
(6, 'Sosialisasi Program Semester Genap', 'Agenda Sekolah', 'SMP Negeri 1 Kotabaru', 'Penyampaian agenda pembelajaran, asesmen, dan kegiatan sekolah kepada orang tua atau wali peserta didik.', '2026-05-05 08:00:00', NULL, 1, 1, '2026-04-30 14:09:42', '2025/2026'),
(7, 'Pekan Literasi dan Numerasi', 'Agenda Sekolah', 'SMP Negeri 1 Kotabaru', 'Pelaksanaan kegiatan kelas, penguatan budaya baca, dan proyek numerasi terintegrasi untuk seluruh tingkat.', '2026-05-11 08:00:00', NULL, 1, 2, '2026-04-30 14:09:42', '2025/2026'),
(8, 'Seleksi Internal Lomba Siswa', 'Agenda Sekolah', 'SMP Negeri 1 Kotabaru', 'Tahap persiapan peserta didik untuk mewakili sekolah dalam ajang akademik dan non-akademik tingkat kabupaten.', '2026-05-18 08:00:00', NULL, 1, 3, '2026-04-30 14:09:42', '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `link_label` varchar(120) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `link_label`, `link_url`, `starts_at`, `ends_at`, `is_active`, `sort_order`, `created_at`) VALUES
(6, 'Layanan Informasi Sekolah Tahun Pelajaran 2025/2026', 'SMP Negeri 1 Kotabaru menyediakan informasi profil sekolah, agenda, dokumen, kegiatan kesiswaan, dan layanan PPDB melalui website resmi ini.', 'Lihat Kontak', '/kontak', NULL, NULL, 1, 1, '2026-04-30 14:09:42'),
(7, 'Penguatan Pembelajaran Mendalam', 'Sekolah mengembangkan pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, dan pengambilan keputusan berbasis data.', 'Lihat Akademik', '/akademik', NULL, NULL, 1, 2, '2026-04-30 14:09:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(60) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `status` enum('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `extracurriculars`
--

CREATE TABLE `extracurriculars` (
  `id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL,
  `coach` varchar(160) DEFAULT NULL,
  `schedule_text` varchar(180) DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `extracurriculars`
--

INSERT INTO `extracurriculars` (`id`, `name`, `category`, `coach`, `schedule_text`, `location`, `description`, `image_path`, `is_active`, `sort_order`, `created_at`) VALUES
(2, 'Jurnalistik', 'Literasi dan Media', 'Yulianoor, S.Pd', 'Kamis: 16.00 s.d 17.15 Wita', 'Ruang Multimedia, Ruang Jurnalis dan Aula', 'Ekstrakurikuler Jurnalistik adalah wadah bagi peserta didik untuk mengembangkan keterampilan literasi, komunikasi, dan berpikir kritis. Di sini, siswa dilatih untuk mengamati fenomena di lingkungan sekolah dan sekitarnya, kemudian mengolahnya menjadi informasi yang akurat, berimbang, dan menarik. Anggota jurnalistik akan mendalami teknik wawancara, penulisan berita, fotografi media, hingga manajemen redaksi majalah atau website sekolah.', '/public/uploads/extracurriculars/extracurriculars-20260430034048-2cd34b8d8d0def3d.webp', 1, 1, '2026-04-30 01:28:54'),
(6, 'Pramuka', 'Wajib', NULL, NULL, 'SMP Negeri 1 Kotabaru', 'Pembinaan kedisiplinan, kemandirian, kerja sama, dan kepedulian sosial melalui kegiatan kepramukaan.', '/public/logo-sekolah.png', 1, 1, '2026-04-30 14:09:42'),
(7, 'PMR', 'Kesehatan', NULL, NULL, 'SMP Negeri 1 Kotabaru', 'Pembinaan kepedulian, kesiapsiagaan, dan layanan dasar kesehatan di lingkungan sekolah.', '/public/logo-sekolah.png', 1, 2, '2026-04-30 14:09:42'),
(8, 'Futsal', 'Olahraga', NULL, NULL, 'Lapangan sekolah', 'Kegiatan pengembangan kebugaran, sportivitas, dan kerja sama tim melalui pembinaan olahraga.', '/public/logo-sekolah.png', 1, 3, '2026-04-30 14:09:42'),
(9, 'Paduan Suara', 'Seni', NULL, NULL, 'SMP Negeri 1 Kotabaru', 'Pengembangan kepekaan seni, disiplin latihan, dan kerja sama melalui kegiatan vokal bersama.', '/public/logo-sekolah.png', 1, 4, '2026-04-30 14:09:42'),
(10, 'Seni Tari', 'Seni', NULL, NULL, 'SMP Negeri 1 Kotabaru', 'Pembinaan kreativitas, apresiasi budaya, dan keberanian tampil melalui kegiatan seni pertunjukan.', '/public/logo-sekolah.png', 1, 5, '2026-04-30 14:09:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `is_active`, `sort_order`, `created_at`) VALUES
(5, 'Di mana alamat SMP Negeri 1 Kotabaru?', 'Alamat sekolah berada di Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117.', 'Profil', 1, 1, '2026-04-30 14:09:43'),
(6, 'Bagaimana cara menghubungi sekolah?', 'Sekolah dapat dihubungi melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan Senin-Jumat pukul 07.00-15.00.', 'Layanan', 1, 2, '2026-04-30 14:09:43'),
(7, 'Apakah informasi PPDB tersedia di website?', 'Ya. Informasi PPDB, persyaratan, catatan penting, dan formulir pendaftaran tersedia pada menu PPDB. Jadwal resmi mengikuti pengumuman sekolah dan dinas terkait.', 'PPDB', 1, 3, '2026-04-30 14:09:43'),
(8, 'Dokumen apa saja yang biasanya diperlukan untuk PPDB?', 'Calon peserta didik biasanya perlu menyiapkan kartu keluarga, akta kelahiran, dokumen identitas atau pendukung sesuai jalur pendaftaran, pas foto, dan dokumen tambahan sesuai pengumuman panitia.', 'PPDB', 1, 4, '2026-04-30 14:09:43'),
(9, 'Apakah sekolah memiliki kegiatan ekstrakurikuler?', 'Ya. Sekolah menyediakan kegiatan pengembangan minat dan bakat seperti Pramuka, Jurnalistik, PMR, Futsal, Paduan Suara, Seni Tari, dan kegiatan lain yang dapat diperbarui melalui menu Ekstrakurikuler.', 'Kesiswaan', 1, 5, '2026-04-30 14:09:43'),
(10, 'Bagaimana cara melihat agenda sekolah?', 'Agenda kegiatan sekolah dapat dilihat melalui menu Agenda. Admin sekolah dapat memperbarui jadwal kegiatan sesuai tahun ajaran aktif.', 'Agenda', 1, 6, '2026-04-30 14:09:43'),
(11, 'Apakah dokumen sekolah bisa diunduh?', 'Ya. Dokumen resmi seperti kalender akademik, formulir, surat edaran, tata tertib, dan file PDF lain dapat diunggah admin dan diunduh melalui menu Dokumen.', 'Dokumen', 1, 7, '2026-04-30 14:09:43'),
(12, 'Apa fokus pengembangan pembelajaran sekolah?', 'Sekolah memperkuat pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, teknologi, dan pengambilan keputusan berbasis data.', 'Akademik', 1, 8, '2026-04-30 14:09:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gallery_items`
--

CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gallery_items`
--

INSERT INTO `gallery_items` (`id`, `title`, `description`, `image_path`, `sort_order`, `created_at`) VALUES
(6, 'Kepala Sekolah', 'Dokumentasi resmi kepala sekolah SMP Negeri 1 Kotabaru.', '/public/uploads/profile/profile-20260430080330-7d11d32190329dcd.webp', 1, '2026-04-30 14:09:42'),
(7, 'Logo Sekolah', 'Identitas visual resmi SMP Negeri 1 Kotabaru.', '/public/logo-sekolah.png', 2, '2026-04-30 14:09:42'),
(17, 'test', 'test', '/public/uploads/gallery/gallery-20260501042042-102f1325d7c22fbd.webp', 0, '2026-05-01 02:20:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `published_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `news`
--

INSERT INTO `news` (`id`, `slug`, `title`, `category`, `excerpt`, `content`, `image_path`, `published_at`, `is_active`, `created_at`) VALUES
(2, 'SMP Negeri 1 Kotabaru Sekolah Ramah Anak', 'Spensa Update', 'Informasi', 'SMP Negeri 1 Kotabaru: Rumah Kedua yang Aman untuk Belajar\r\n\r\nSMP Negeri 1 Kotabaru resmi mengukuhkan komitmennya sebagai Sekolah Ramah Anak (SRA). Melalui inisiatif ini, sekolah menjamin lingkungan belajar yang bebas dari perundungan, diskriminasi, dan kekerasan.\r\n\r\nPoin Utama:\r\n\r\nPerlindungan Siswa: Menjamin keamanan fisik dan psikologis seluruh peserta didik.\r\n\r\nPendekatan Humanis: Membangun komunikasi yang efektif antara guru dan siswa berdasarkan kasih sayang.\r\n\r\nFasilitas Inklusif: Menyediakan sarana prasarana yang mendukung kesehatan dan kenyamanan anak.\r\n\r\nPartisipasi Anak: Memberikan ruang bagi siswa untuk berpendapat dan berinovasi sesuai minat bakatnya.\r\n\r\nDengan semangat #PulangKeDiri, SMPN 1 Kotabaru mengajak seluruh warga sekolah untuk saling menjaga dan menghargai, demi mencetak generasi unggul yang berkarakter kuat dan berakar pada budaya lokal.', 'Berikut adalah draf redaksi berita formal dan ringkasannya untuk mempromosikan profil SMP Negeri 1 Kotabaru sebagai Sekolah Ramah Anak (SRA).\r\n\r\nRedaksi Berita (Versi Lengkap)\r\nWujudkan Lingkungan Belajar yang Aman dan Nyaman, SMP Negeri 1 Kotabaru Kukuhkan Diri sebagai Sekolah Ramah Anak\r\n\r\nKOTABARU – SMP Negeri 1 Kotabaru terus berkomitmen menciptakan ekosistem pendidikan yang kondusif bagi tumbuh kembang siswa. Melalui program Sekolah Ramah Anak (SRA), sekolah ini memastikan setiap peserta didik mendapatkan perlindungan, keadilan, dan hak-haknya dalam menuntut ilmu tanpa rasa takut.\r\n\r\nKepala SMP Negeri 1 Kotabaru menyatakan bahwa konsep Sekolah Ramah Anak bukan sekadar label, melainkan sebuah aksi nyata. \"Kami mengedepankan pendekatan yang humanis. Di sini, guru bukan hanya sebagai pengajar, tetapi juga sebagai sahabat dan pelindung bagi siswa. Kami berkomitmen menciptakan sekolah tanpa perundungan (zero bullying), tanpa kekerasan fisik, dan penuh kasih sayang,\" ujarnya.\r\n\r\nSelain aspek keamanan fisik dan mental, SMPN 1 Kotabaru juga mengintegrasikan nilai-nilai Sekolah Ramah Anak dalam sarana prasarana yang mendukung kesehatan serta inklusivitas. Seluruh warga sekolah, mulai dari staf hingga orang tua, berkolaborasi secara aktif untuk memastikan suara anak didengar dan dihargai.\r\n\r\nLangkah ini sejalan dengan visi sekolah untuk mewujudkan peserta didik yang berakhlak mulia dan memiliki kemampuan kolaborasi yang baik. Dengan lingkungan yang \"meredam\" tekanan dan memberikan ruang aman untuk berekspresi, diharapkan siswa dapat berpikir lebih kritis dan inovatif demi masa depan yang berkelanjutan.', '/public/uploads/news/news-20260430034429-07f2e40db85b56c7.webp', '2026-04-30 09:45:00', 1, '2026-04-30 01:44:30'),
(9, 'program-pembelajaran-mendalam-smp-negeri-1-kotabaru', 'Program Pembelajaran Mendalam SMP Negeri 1 Kotabaru', 'Akademik', 'Sekolah memperkuat kepemimpinan pembelajaran, budaya kolaboratif, dan transformasi manajemen sekolah melalui siklus inkuiri kolaboratif.', 'SMP Negeri 1 Kotabaru melaksanakan Program Pembelajaran Mendalam Kepala Sekolah dengan pendekatan Siklus Inkuiri Kolaboratif.\n\nProgram ini mendorong eksplorasi praktik terbaik, refleksi kritis terhadap konteks lokal, dan pengambilan keputusan berbasis data yang melibatkan guru, tenaga kependidikan, peserta didik, komite sekolah, orang tua, dan mitra eksternal.\n\nArah program difokuskan pada penyelarasan visi, misi, dan tujuan sekolah, penguatan pembelajaran berbasis proyek, budaya lokal, teknologi, serta pengembangan komunitas belajar yang adaptif dan berkelanjutan.', '/public/logo-sekolah.png', '2025-10-12 08:00:00', 1, '2026-04-30 14:09:42'),
(10, 'profil-data-sekolah-desember-2025', 'Profil Data Sekolah Desember 2025', 'Profil Sekolah', 'Laporan bulanan mencatat SMP Negeri 1 Kotabaru memiliki 775 siswa dalam 24 rombongan belajar pada tahun pelajaran 2025/2026.', 'Berdasarkan laporan bulanan sekolah bulan Desember 2025, SMP Negeri 1 Kotabaru menyelenggarakan pendidikan pada tahun pelajaran 2025/2026 dengan 775 peserta didik.\n\nJumlah tersebut terdiri dari 376 siswa laki-laki dan 399 siswa perempuan yang tersebar dalam 24 rombongan belajar.\n\nData ini menjadi bagian dari dasar pengelolaan layanan akademik, kesiswaan, sarana prasarana, serta perencanaan program sekolah.', '/public/logo-sekolah.png', '2025-12-31 08:00:00', 1, '2026-04-30 14:09:42'),
(11, 'tes-kemampuan-akademik-berjalan-lancar', 'Tes Kemampuan Akademik Berjalan Lancar', 'Akademik', 'Pelaksanaan asesmen berlangsung tertib dengan semangat belajar yang tinggi dari seluruh siswa.', 'Pelaksanaan tes kemampuan akademik di SMP Negeri 1 Kotabaru berlangsung tertib, terjadwal, dan diikuti peserta didik dengan kesiapan yang baik.\n\nKegiatan ini menjadi bagian dari evaluasi pembelajaran dan bahan tindak lanjut penguatan materi oleh guru mata pelajaran dan wali kelas.\n\nSekolah berharap kegiatan evaluasi seperti ini juga menanamkan sikap disiplin, jujur, dan tanggung jawab.', '/public/logo-sekolah.png', '2026-04-15 00:00:00', 1, '2026-04-30 14:09:42'),
(12, 'ekstrakurikuler-jurnalistik-aktif-berkarya', 'Ekstrakurikuler Jurnalistik Aktif Berkarya', 'Kesiswaan', 'Siswa terus mengembangkan kemampuan menulis, wawancara, dan publikasi sekolah.', 'Kegiatan ekstrakurikuler jurnalistik menunjukkan perkembangan positif melalui latihan penulisan berita, wawancara, dan dokumentasi sekolah.\r\n\r\nPeserta didik dibimbing memahami unsur berita dan menyusun narasi informatif secara bertanggung jawab.\r\n\r\nProgram ini mendukung budaya literasi sekaligus pengembangan minat dan bakat siswa.', '/public/uploads/news/news-20260505132244-b9502ee7951b5d6f.webp', '2026-04-12 00:00:00', 1, '2026-04-30 14:09:42'),
(13, 'persiapan-video-profil-sekolah', 'Persiapan Video Profil Sekolah', 'Publikasi', 'Tim sekolah menyiapkan dokumentasi terbaik untuk memperkuat branding dan informasi publik.', 'Sekolah menyiapkan video profil sebagai media informasi visual untuk memperkenalkan lingkungan belajar dan program unggulan.\n\nDokumentasi dilakukan bertahap agar hasil yang ditampilkan tetap representatif dan sesuai identitas lembaga.\n\nMateri video diharapkan bermanfaat untuk website, media sosial, dan publikasi resmi lainnya.', '/public/logo-sekolah.png', '2026-04-10 00:00:00', 1, '2026-04-30 14:09:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ppdb_applications`
--

CREATE TABLE `ppdb_applications` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(40) NOT NULL,
  `student_name` varchar(160) NOT NULL,
  `birth_place` varchar(120) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('Laki-laki','Perempuan') NOT NULL,
  `nisn` varchar(40) NOT NULL,
  `previous_school` varchar(160) NOT NULL,
  `registration_path` varchar(40) NOT NULL DEFAULT '',
  `parent_name` varchar(160) NOT NULL,
  `parent_phone` varchar(60) NOT NULL,
  `parent_email` varchar(180) NOT NULL,
  `address` text NOT NULL,
  `family_card_path` varchar(255) NOT NULL,
  `birth_certificate_path` varchar(255) NOT NULL,
  `report_card_path` varchar(255) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `consent_accepted_at` datetime DEFAULT NULL,
  `privacy_version` varchar(40) DEFAULT NULL,
  `status` enum('baru','diverifikasi','diterima','ditolak') NOT NULL DEFAULT 'baru',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ppdb_entries`
--

CREATE TABLE `ppdb_entries` (
  `id` int(11) NOT NULL,
  `section` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ppdb_entries`
--

INSERT INTO `ppdb_entries` (`id`, `section`, `title`, `content`, `sort_order`, `created_at`) VALUES
(4, 'alur', 'Langkah 1', 'Membaca informasi umum, persyaratan, dan jadwal pendaftaran peserta didik baru.', 1, '2026-04-30 14:09:43'),
(5, 'alur', 'Langkah 2', 'Menyiapkan dokumen yang dipersyaratkan sesuai ketentuan panitia PPDB sekolah.', 2, '2026-04-30 14:09:43'),
(6, 'alur', 'Langkah 3', 'Mengisi formulir pendaftaran sesuai jalur yang dibuka oleh sekolah.', 3, '2026-04-30 14:09:43'),
(7, 'alur', 'Langkah 4', 'Mengikuti proses verifikasi berkas serta menunggu pengumuman hasil seleksi sesuai jadwal.', 4, '2026-04-30 14:09:43'),
(8, 'informasi', 'Jalur Pendaftaran', 'Informasi jalur pendaftaran mengikuti pengumuman resmi PPDB tahun pelajaran berjalan dari sekolah dan dinas terkait.', 1, '2026-04-30 14:09:43'),
(9, 'informasi', 'Jadwal Pelaksanaan', 'Jadwal pendaftaran, verifikasi berkas, pengumuman hasil seleksi, dan daftar ulang akan dipublikasikan melalui kanal resmi sekolah.', 2, '2026-04-30 14:09:43'),
(10, 'informasi', 'Layanan Panitia', 'Calon peserta didik dan orang tua dapat menghubungi sekolah melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan.', 3, '2026-04-30 14:09:43'),
(11, 'persyaratan', 'Persyaratan 1', 'Fotokopi kartu keluarga atau dokumen identitas keluarga yang masih berlaku', 1, '2026-04-30 14:09:43'),
(12, 'persyaratan', 'Persyaratan 2', 'Fotokopi akta kelahiran calon peserta didik', 2, '2026-04-30 14:09:43'),
(13, 'persyaratan', 'Persyaratan 3', 'Fotokopi kartu identitas atau dokumen pendukung sesuai ketentuan jalur pendaftaran', 3, '2026-04-30 14:09:43'),
(14, 'persyaratan', 'Persyaratan 4', 'Pas foto terbaru sesuai kebutuhan administrasi sekolah', 4, '2026-04-30 14:09:43'),
(15, 'persyaratan', 'Persyaratan 5', 'Dokumen tambahan lain sesuai pengumuman resmi panitia PPDB sekolah', 5, '2026-04-30 14:09:43'),
(16, 'catatan', 'Catatan 1', 'Pastikan data calon peserta didik diisi sesuai dokumen resmi keluarga dan sekolah asal.', 1, '2026-04-30 14:09:43'),
(17, 'catatan', 'Catatan 2', 'Berkas pendaftaran diverifikasi oleh panitia PPDB sesuai ketentuan yang berlaku.', 2, '2026-04-30 14:09:43'),
(18, 'catatan', 'Catatan 3', 'Perubahan jadwal atau ketentuan PPDB akan diinformasikan melalui media resmi sekolah.', 3, '2026-04-30 14:09:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_achievements`
--

CREATE TABLE `school_achievements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `level` varchar(120) NOT NULL,
  `category` varchar(120) NOT NULL,
  `achiever_name` varchar(180) NOT NULL,
  `achievement_date` date NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `school_achievements`
--

INSERT INTO `school_achievements` (`id`, `title`, `level`, `category`, `achiever_name`, `achievement_date`, `description`, `image_path`, `is_active`, `sort_order`, `created_at`, `academic_year`) VALUES
(5, 'Akreditasi A SMP Negeri 1 Kotabaru', 'Nasional', 'Sekolah', 'SMP Negeri 1 Kotabaru', '2023-05-25', 'SMP Negeri 1 Kotabaru memperoleh Akreditasi A berdasarkan SK No. 555/BAN-SM/SK/2023 tanggal 25 Mei 2023.', '/public/logo-sekolah.png', 1, 1, '2026-04-30 14:09:42', '2025/2026'),
(6, 'KABAR PRESTASI: Kilau Bakat di Panggung SMADAFest!', 'Sekolah Menengah Pertama', 'Non-Akademik', 'Juara 1 Cabang Lomba Modeling', '2026-04-30', 'Keluarga besar SMPN 1 Kotabaru bangga! Selamat kepada Faiza Nabila Mufida (IX B) yang baru saja mengukir prestasi gemilang sebagai Juara 1 Cabang Modeling Tingkat SMP dalam ajang SMADAFest di SMA Negeri 2 Kotabaru (30/04).\r\n\r\nLangkah kaki yang percaya diri dan pesona di atas runway telah membawa nama Spensa harum di tingkat kabupaten. Semoga prestasi ini menjadi pemantik semangat bagi kita semua untuk terus berani menunjukkan bakat unik kita masing-masing.\r\n\r\nKeep shining, Faiza! 🏆', '/public/uploads/achievements/achievements-20260501095347-7f51b40a4b5a22d7.webp', 1, 1, '2026-04-30 14:09:42', '2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_documents`
--

CREATE TABLE `school_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_facilities`
--

CREATE TABLE `school_facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL,
  `location` varchar(180) DEFAULT NULL,
  `capacity` varchar(120) DEFAULT NULL,
  `condition_status` varchar(120) NOT NULL DEFAULT 'Baik',
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `school_facilities`
--

INSERT INTO `school_facilities` (`id`, `name`, `category`, `location`, `capacity`, `condition_status`, `description`, `image_path`, `is_active`, `sort_order`, `created_at`) VALUES
(2, 'Ruang Teori/Kelas', 'Ruang Belajar', NULL, '24 ruang, luas 1.590 m2', 'Baik', 'Fasilitas ruang kelas untuk kegiatan belajar mengajar.', '/public/logo-sekolah.png', 1, 1, '2026-04-30 04:31:05'),
(3, 'Laboratorium IPA', 'Laboratorium', NULL, '1 ruang baik 150 m2 dan 1 ruang 132 m2', 'Baik', 'Laboratorium pendukung praktik dan pembelajaran IPA.', '/public/logo-sekolah.png', 1, 2, '2026-04-30 04:31:05'),
(4, 'Laboratorium Fisika', 'Laboratorium', NULL, '1 ruang, luas 150 m2', 'Baik', 'Laboratorium pendukung praktik dan pembelajaran Fisika.', '/public/logo-sekolah.png', 1, 3, '2026-04-30 04:31:05'),
(5, 'Laboratorium Bahasa', 'Laboratorium', NULL, '2 ruang, luas 558 m2', 'Baik', 'Laboratorium untuk pembelajaran bahasa dan keterampilan komunikasi.', '/public/logo-sekolah.png', 1, 4, '2026-04-30 04:31:05'),
(6, 'Laboratorium Komputer', 'Laboratorium', NULL, '1 ruang, luas 148 m2', 'Baik', 'Laboratorium pendukung pembelajaran informatika dan teknologi.', '/public/logo-sekolah.png', 1, 5, '2026-04-30 04:31:05'),
(7, 'Ruang Perpustakaan', 'Literasi', NULL, '1 ruang, luas 135 m2', 'Baik', 'Ruang layanan literasi, referensi, dan aktivitas baca peserta didik.', '/public/logo-sekolah.png', 1, 6, '2026-04-30 04:31:05'),
(8, 'Ruang Keterampilan', 'Pengembangan Diri', NULL, '1 ruang, luas 105 m2', 'Baik', 'Ruang pendukung pembelajaran keterampilan dan praktik kreatif.', '/public/logo-sekolah.png', 1, 7, '2026-04-30 04:31:05'),
(9, 'Ruang Serba Guna', 'Kegiatan Sekolah', NULL, '1 ruang, luas 300 m2', 'Baik', 'Ruang untuk kegiatan sekolah, pertemuan, dan aktivitas bersama.', '/public/logo-sekolah.png', 1, 8, '2026-04-30 04:31:05'),
(10, 'Ruang UKS', 'Layanan Siswa', NULL, '1 ruang, luas 42 m2', 'Baik', 'Ruang layanan kesehatan sekolah bagi peserta didik.', '/public/logo-sekolah.png', 1, 9, '2026-04-30 04:31:05'),
(11, 'Ruang BP/BK', 'Layanan Siswa', NULL, '1 ruang, luas 105 m2', 'Baik', 'Ruang layanan bimbingan dan konseling peserta didik.', '/public/logo-sekolah.png', 1, 10, '2026-04-30 04:31:05'),
(12, 'Ruang Guru', 'Administrasi Sekolah', NULL, '1 ruang, luas 185 m2', 'Baik', 'Ruang kerja dan koordinasi pendidik.', '/public/logo-sekolah.png', 1, 11, '2026-04-30 04:31:05'),
(13, 'Ruang OSIS', 'Kesiswaan', NULL, '1 ruang, luas 42 m2', 'Baik', 'Ruang aktivitas organisasi siswa.', '/public/logo-sekolah.png', 1, 12, '2026-04-30 04:31:05'),
(14, 'Kamar Mandi/WC Guru', 'Sanitasi', NULL, '1 unit, luas 70 m2', 'Baik', 'Fasilitas sanitasi untuk guru dan tenaga kependidikan.', '/public/logo-sekolah.png', 1, 13, '2026-04-30 04:31:05'),
(15, 'Kamar Mandi/WC Siswa', 'Sanitasi', NULL, '2 unit, luas 27 m2', 'Baik', 'Fasilitas sanitasi untuk peserta didik.', '/public/logo-sekolah.png', 1, 14, '2026-04-30 04:31:05'),
(16, 'Gudang', 'Penunjang', NULL, '1 ruang, luas 160 m2', 'Baik', 'Ruang penyimpanan sarana penunjang sekolah.', '/public/logo-sekolah.png', 1, 15, '2026-04-30 04:31:05'),
(17, 'Ruang Ibadah', 'Keagamaan', NULL, '1 ruang, luas 124 m2', 'Baik', 'Fasilitas ibadah dan pembinaan karakter religius.', '/public/logo-sekolah.png', 1, 16, '2026-04-30 04:31:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'schoolProfile', '{\n    \"name\": \"SMP Negeri 1 Kotabaru\",\n    \"shortName\": \"SMPN 1 Kotabaru\",\n    \"tagline\": \"Sekolah Aktif, Berkarakter, dan Berprestasi\",\n    \"description\": \"Website resmi sekolah sebagai sarana publikasi, layanan informasi, dan komunikasi kelembagaan yang mendukung transformasi pembelajaran, budaya kolaboratif, serta peningkatan mutu pendidikan.\",\n    \"heroDescription\": \"Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.\",\n    \"address\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\",\n    \"phone\": \"(0518) 21602\",\n    \"email\": \"smpn1_54ktb@yahoo.co.id\",\n    \"serviceHours\": \"Senin-Jumat, 07.00-15.00\",\n    \"logoPath\": \"\\/public\\/uploads\\/profile\\/profile-20260506134639-c5ae45672269a73d.webp\"\n}', '2026-05-06 11:46:40'),
(2, 'principalMessage', '{\n    \"name\": \"Rusdiansyah, S.Pd.\",\n    \"title\": \"Kepala Sekolah\",\n    \"greeting\": \"Assalamu\'alaikum warahmatullahi wabarakatuh. Selamat datang di website resmi SMP Negeri 1 Kotabaru.\",\n    \"photoPath\": \"\\/public\\/uploads\\/profile\\/profile-20260430080330-7d11d32190329dcd.webp\",\n    \"paragraphs\": [\n        \"Website ini kami hadirkan sebagai media informasi resmi sekolah yang dapat mempererat komunikasi antara sekolah, peserta didik, orang tua, dan masyarakat.\",\n        \"SMP Negeri 1 Kotabaru berkomitmen memperkuat pembelajaran mendalam melalui budaya reflektif, kolaboratif, adaptif, dan berpusat pada kebutuhan peserta didik.\",\n        \"Kami terus mendorong guru, tenaga kependidikan, peserta didik, orang tua, komite sekolah, dan mitra eksternal untuk terlibat dalam peningkatan mutu pendidikan secara berkelanjutan.\"\n    ]\n}', '2026-04-30 06:03:30'),
(3, 'stats', '[\n    {\n        \"label\": \"Siswa\",\n        \"value\": \"775\"\n    },\n    {\n        \"label\": \"Guru & Staf\",\n        \"value\": \"65\"\n    },\n    {\n        \"label\": \"Ekstrakurikuler\",\n        \"value\": \"19\"\n    },\n    {\n        \"label\": \"Rombel\",\n        \"value\": \"24\"\n    }\n]', '2026-04-30 04:35:15'),
(4, 'profileSections', '[\n    {\n        \"title\": \"Sejarah Singkat\",\n        \"text\": \"SMP Negeri 1 Kotabaru merupakan institusi pendidikan yang berkomitmen terhadap transformasi mutu pembelajaran melalui penguatan kepemimpinan pembelajaran, budaya kolaboratif, dan pengambilan keputusan berbasis data. Sekolah mendorong proses pendidikan yang adaptif, inklusif, reflektif, serta relevan dengan kebutuhan peserta didik dan tantangan abad ke-21.\"\n    },\n    {\n        \"title\": \"Visi\",\n        \"text\": \"Mewujudkan peserta didik yang beriman dan berakhlak mulia, berpikir kritis, kreatif, dan reflektif, serta mampu berinovasi dan berkolaborasi dalam teknologi, berakar pada budaya lokal, dan berorientasi pada keberlanjutan.\"\n    },\n    {\n        \"title\": \"Misi\",\n        \"text\": \"1. Merancang pembelajaran berbasis eksplorasi, refleksi, dan kolaborasi. 2. Mengintegrasikan pembelajaran berbasis proyek dan budaya lokal. 3. Meningkatkan manajemen pendidikan yang adaptif dan ramah anak melalui kebijakan pembelajaran yang mendorong otonomi profesional guru serta partisipasi aktif murid. 4. Merancang strategi pembelajaran diferensiatif dan berbasis kekuatan murid. 5. Menjalin kemitraan pembelajaran berbasis komunitas dan CSR. 6. Menyusun kurikulum yang menumbuhkan pemikiran reflektif dan inovatif. 7. Mendorong guru sebagai fasilitator pembelajaran mendalam melalui refleksi dan praktik inovatif. 8. Menjadikan sekolah sebagai komunitas pembelajar yang adaptif dan berkelanjutan.\"\n    }\n]', '2026-04-30 04:35:10'),
(5, 'schoolFacilities', '[\n    \"24 ruang teori\\/kelas\",\n    \"Laboratorium IPA\",\n    \"Laboratorium Fisika\",\n    \"2 laboratorium bahasa\",\n    \"Laboratorium komputer\",\n    \"Ruang perpustakaan\",\n    \"Ruang keterampilan\",\n    \"Ruang serba guna\",\n    \"Ruang UKS\",\n    \"Ruang BP\\/BK\",\n    \"Ruang guru\",\n    \"Ruang OSIS\",\n    \"Kamar mandi\\/WC guru dan siswa\",\n    \"Gudang\",\n    \"Ruang ibadah\"\n]', '2026-04-30 04:31:05'),
(6, 'schoolIdentity', '[\n    {\n        \"label\": \"Nama Sekolah\",\n        \"value\": \"SMP Negeri 1 Kotabaru\"\n    },\n    {\n        \"label\": \"Status\",\n        \"value\": \"Sekolah Menengah Pertama Negeri\"\n    },\n    {\n        \"label\": \"NPSN\",\n        \"value\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\"\n    },\n    {\n        \"label\": \"NSS\",\n        \"value\": \"Senin-Jumat, 07.00-15.00\"\n    },\n    {\n        \"label\": \"Alamat\",\n        \"value\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\"\n    },\n    {\n        \"label\": \"Akreditasi\",\n        \"value\": \"A - SK No. 555\\/BAN-SM\\/SK\\/2023 tanggal 25 Mei 2023\"\n    },\n    {\n        \"label\": \"Tahun Berdiri\",\n        \"value\": \"1954\"\n    },\n    {\n        \"label\": \"Jam Layanan\",\n        \"value\": \"Senin-Jumat, 07.00-15.00\"\n    },\n    {\n        \"label\": \"Email Resmi\",\n        \"value\": \"smpn1_54ktb@yahoo.co.id\"\n    },\n    {\n        \"label\": \"Telepon\",\n        \"value\": \"(0518) 21602\"\n    }\n]', '2026-05-01 00:34:40'),
(7, 'contactChannels', '[\n    {\n        \"title\": \"Alamat Sekolah\",\n        \"value\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\",\n        \"desc\": \"Alamat resmi sekolah sesuai laporan bulanan sekolah.\"\n    },\n    {\n        \"title\": \"Telepon\",\n        \"value\": \"(0518) 21602\",\n        \"desc\": \"Nomor telepon resmi sekolah yang dapat dihubungi pada jam layanan administrasi.\"\n    },\n    {\n        \"title\": \"Email Resmi\",\n        \"value\": \"smpn1_54ktb@yahoo.co.id\",\n        \"desc\": \"Alamat surat elektronik resmi sekolah untuk layanan informasi dan korespondensi.\"\n    },\n    {\n        \"title\": \"Jam Layanan\",\n        \"value\": \"Senin-Jumat, 07.00-15.00\",\n        \"desc\": \"Jam layanan sekolah untuk administrasi, komunikasi orang tua, dan keperluan informasi umum.\"\n    }\n]', '2026-04-30 04:31:05'),
(11, 'navigation', '[\n    {\n        \"href\": \"\\/\",\n        \"label\": \"Beranda\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/profil\",\n        \"label\": \"Profil\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/fasilitas\",\n        \"label\": \"Fasilitas\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/akademik\",\n        \"label\": \"Akademik\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/agenda\",\n        \"label\": \"Agenda\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/guru-staf\",\n        \"label\": \"Guru & Staf\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/dokumen\",\n        \"label\": \"Dokumen\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/prestasi\",\n        \"label\": \"Prestasi\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/kesiswaan\",\n        \"label\": \"Kesiswaan\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/ekstrakurikuler\",\n        \"label\": \"Ekskul\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/berita\",\n        \"label\": \"Berita\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/galeri\",\n        \"label\": \"Galeri\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/ppdb\",\n        \"label\": \"PPDB\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/faq\",\n        \"label\": \"FAQ\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/kontak\",\n        \"label\": \"Kontak\",\n        \"visible\": false\n    }\n]', '2026-05-06 10:59:13'),
(34, 'siteMaintenance', '{\n    \"enabled\": false,\n    \"message\": \"Website sekolah sedang dalam pemeliharaan singkat. Silakan kembali beberapa saat lagi.\"\n}', '2026-04-29 10:54:20'),
(44, 'siteTheme', '{\n    \"primaryColor\": \"#003d5c\",\n    \"primaryDarkColor\": \"#224b81\",\n    \"secondaryColor\": \"#077488\",\n    \"accentColor\": \"#ead080\",\n    \"darkColor\": \"#0f172a\"\n}', '2026-05-01 00:34:40'),
(173, 'excellencePrograms', '[\n    \"Pelaksanaan pembelajaran berbasis eksplorasi, refleksi, proyek, dan kolaborasi.\",\n    \"Penguatan budaya inkuiri kolaboratif melalui keterlibatan guru, peserta didik, orang tua, komite, dan mitra sekolah.\",\n    \"Pengembangan pembelajaran berbasis budaya lokal, teknologi, dan konteks kehidupan nyata peserta didik.\",\n    \"Penguatan komunitas belajar guru melalui forum diskusi, refleksi praktik, dan berbagi praktik baik.\"\n]', '2026-04-30 04:21:02'),
(174, 'schoolHighlights', '[\n    {\n        \"title\": \"Kepemimpinan Pembelajaran\",\n        \"desc\": \"Sekolah memperkuat peran kepala sekolah sebagai pemimpin pembelajaran, fasilitator perubahan pedagogis, dan penggerak budaya kolaboratif.\"\n    },\n    {\n        \"title\": \"Pembelajaran Mendalam\",\n        \"desc\": \"Pembelajaran diarahkan pada pemahaman bermakna, eksplorasi lintas disiplin, refleksi, kolaborasi, dan pengembangan karakter pembelajar sepanjang hayat.\"\n    },\n    {\n        \"title\": \"Budaya Reflektif\",\n        \"desc\": \"Warga sekolah dilibatkan dalam proses inkuiri, pengambilan keputusan berbasis data, dokumentasi praktik baik, dan perbaikan berkelanjutan.\"\n    }\n]', '2026-04-30 04:21:02'),
(177, 'schoolObjectives', '[\n    \"Dalam 1 tahun, 100% guru menerapkan minimal dua model pembelajaran mendalam berbasis proyek.\",\n    \"Dalam 1 tahun, 80% peserta didik menghasilkan karya berbasis budaya lokal dalam kegiatan P5.\",\n    \"Dalam 4 tahun, 90% lulusan menunjukkan capaian minimal lima dimensi Profil Pelajar Pancasila.\",\n    \"Dalam 2 tahun, seluruh kelas menerapkan pembelajaran berbasis refleksi dan kolaborasi.\",\n    \"Dalam 1 tahun, minimal tiga kegiatan pembelajaran melibatkan orang tua dan mitra komunitas.\"\n]', '2026-04-30 04:21:02'),
(178, 'academicPrograms', '[\n    {\n        \"title\": \"Pembelajaran Mendalam\",\n        \"desc\": \"Pembelajaran dirancang berbasis eksplorasi, refleksi, kolaborasi, proyek, dan pemecahan masalah agar peserta didik membangun pemahaman yang bermakna.\"\n    },\n    {\n        \"title\": \"Kurikulum Berbasis Proyek dan Konteks\",\n        \"desc\": \"Program pembelajaran mengintegrasikan proyek otentik, budaya lokal, Profil Pelajar Pancasila, serta konteks kehidupan nyata peserta didik.\"\n    },\n    {\n        \"title\": \"Komunitas Belajar Guru\",\n        \"desc\": \"Guru difasilitasi melalui In House Training, forum diskusi rutin, coaching reflektif, dan berbagi praktik baik dalam komunitas belajar sekolah.\"\n    }\n]', '2026-04-30 04:21:02'),
(179, 'academicServices', '[\n    {\n        \"title\": \"Asesmen Formatif Berkelanjutan\",\n        \"desc\": \"Asesmen dilakukan untuk memberi umpan balik reflektif, memantau perkembangan pemahaman, dan mendorong perbaikan pembelajaran.\"\n    },\n    {\n        \"title\": \"Asesmen Kinerja Otentik\",\n        \"desc\": \"Penilaian dilakukan melalui portofolio, proyek, presentasi, dan karya peserta didik yang menunjukkan pemahaman serta keterampilan.\"\n    },\n    {\n        \"title\": \"Pendampingan dan Coaching Guru\",\n        \"desc\": \"Kepala sekolah, wakil kepala kurikulum, dan guru fasilitator mendampingi implementasi pembelajaran mendalam di kelas.\"\n    },\n    {\n        \"title\": \"Refleksi dan Perbaikan Berkelanjutan\",\n        \"desc\": \"Sekolah menggunakan survei, observasi kelas, analisis perangkat ajar, jurnal refleksi, dan diskusi kelompok untuk merumuskan perbaikan.\"\n    }\n]', '2026-04-30 04:21:02'),
(180, 'academicSupportItems', '[\n    \"Sosialisasi VMT dan pembelajaran mendalam kepada warga sekolah\",\n    \"Komunitas Belajar Guru dan forum Sabtu Berbagi\",\n    \"RPP pembelajaran mendalam berbasis proyek dan refleksi\",\n    \"Observasi kelas dan coaching reflektif\",\n    \"Bank data digital praktik baik guru\",\n    \"Keterlibatan orang tua, komite, komunitas, CSR, dan mitra eksternal\"\n]', '2026-04-30 04:21:02'),
(181, 'academicCommitments', '[\n    \"Menyelenggarakan pembelajaran yang eksploratif, reflektif, kolaboratif, dan berbasis proyek.\",\n    \"Menguatkan budaya inkuiri kolaboratif dan pengambilan keputusan berbasis data.\",\n    \"Mengembangkan sekolah sebagai komunitas pembelajar yang adaptif, inklusif, dan berkelanjutan.\"\n]', '2026-04-30 04:21:02'),
(198, 'ppdbInformation', '[\n    {\n        \"title\": \"Jalur Pendaftaran\",\n        \"desc\": \"Informasi jalur pendaftaran mengikuti pengumuman resmi PPDB tahun pelajaran berjalan dari sekolah dan dinas terkait.\"\n    },\n    {\n        \"title\": \"Jadwal Pelaksanaan\",\n        \"desc\": \"Jadwal pendaftaran, verifikasi berkas, pengumuman hasil seleksi, dan daftar ulang akan dipublikasikan melalui kanal resmi sekolah.\"\n    },\n    {\n        \"title\": \"Layanan Panitia\",\n        \"desc\": \"Calon peserta didik dan orang tua dapat menghubungi sekolah melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan.\"\n    }\n]', '2026-04-30 04:35:15'),
(199, 'ppdbNotes', '[\n    \"Pastikan data calon peserta didik diisi sesuai dokumen resmi keluarga dan sekolah asal.\",\n    \"Berkas pendaftaran diverifikasi oleh panitia PPDB sesuai ketentuan yang berlaku.\",\n    \"Perubahan jadwal atau ketentuan PPDB akan diinformasikan melalui media resmi sekolah.\"\n]', '2026-04-30 04:35:15'),
(200, 'contactNotes', '[\n    \"Pengunjung diharapkan menggunakan saluran komunikasi resmi sekolah untuk memperoleh informasi yang akurat.\",\n    \"Pertanyaan yang berkaitan dengan peserta didik dapat disampaikan pada jam layanan agar proses tindak lanjut lebih terarah.\",\n    \"Informasi website dan media sosial resmi dapat dilengkapi kembali setelah kanal resmi sekolah tersedia.\"\n]', '2026-04-30 04:35:15'),
(218, 'siteAcademic', '{\n    \"currentYear\": \"2025\\/2026\"\n}', '2026-05-01 00:34:40'),
(219, 'siteHome', '{\n    \"heroTitle\": \"SMP Negeri 1 Kotabaru\",\n    \"heroSubtitle\": \"Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.\",\n    \"primaryButtonLabel\": \"Lihat Profil Sekolah\",\n    \"primaryButtonUrl\": \"\\/profil\",\n    \"secondaryButtonLabel\": \"Informasi PPDB\",\n    \"secondaryButtonUrl\": \"\\/ppdb\",\n    \"profileVideoUrl\": \"https:\\/\\/youtu.be\\/MG0kwS2g_B8?si=t7C7BWE1-sOF0ong\",\n    \"profileVideoTitle\": \"Profil SMP Negeri 1 Kotabaru\",\n    \"profileVideoDescription\": \"Ruang ini disiapkan untuk menampilkan video profil resmi sekolah, mulai dari lingkungan belajar, kegiatan siswa, fasilitas, hingga budaya sekolah.\",\n    \"teacherLimit\": 10,\n    \"agendaLimit\": 3,\n    \"showAnnouncements\": true,\n    \"showQuickLinks\": true,\n    \"showLogoCard\": true,\n    \"showStats\": true,\n    \"showPrincipalCard\": true,\n    \"showTeacherCarousel\": true,\n    \"showProfileIntro\": true,\n    \"showFeaturedAreas\": true,\n    \"showAgenda\": true\n}', '2026-05-06 10:46:52'),
(223, 'quickLinks', '[\n    {\n        \"title\": \"Profil Sekolah\",\n        \"desc\": \"Sejarah singkat, visi, misi, dan gambaran umum penyelenggaraan pendidikan di sekolah.\",\n        \"href\": \"\\/profil\"\n    },\n    {\n        \"title\": \"Fasilitas Sekolah\",\n        \"desc\": \"Ruang belajar, laboratorium, perpustakaan, lapangan, dan sarana pendukung kegiatan sekolah.\",\n        \"href\": \"\\/fasilitas\"\n    },\n    {\n        \"title\": \"Agenda Akademik\",\n        \"desc\": \"Kalender pendidikan, asesmen, dan jadwal kegiatan akademik penting.\",\n        \"href\": \"\\/akademik\"\n    },\n    {\n        \"title\": \"Ekstrakurikuler\",\n        \"desc\": \"Program pengembangan minat, bakat, dan pembinaan karakter peserta didik.\",\n        \"href\": \"\\/ekstrakurikuler\"\n    },\n    {\n        \"title\": \"Informasi PPDB\",\n        \"desc\": \"Alur, persyaratan, dan jadwal penerimaan peserta didik baru.\",\n        \"href\": \"\\/ppdb\"\n    }\n]', '2026-05-01 00:34:40'),
(224, 'featuredAreas', '[\n    {\n        \"title\": \"Akademik\",\n        \"desc\": \"Informasi pembelajaran, asesmen, agenda akademik, dan layanan pendidikan sekolah.\",\n        \"href\": \"\\/akademik\"\n    },\n    {\n        \"title\": \"Kesiswaan\",\n        \"desc\": \"Informasi organisasi siswa, ekstrakurikuler, pembinaan karakter, dan prestasi peserta didik.\",\n        \"href\": \"\\/kesiswaan\"\n    },\n    {\n        \"title\": \"Publikasi\",\n        \"desc\": \"Berita sekolah, dokumentasi kegiatan, galeri, dan informasi publik resmi sekolah.\",\n        \"href\": \"\\/berita\"\n    }\n]', '2026-05-01 00:34:40'),
(229, 'schoolAccreditation', '{\n    \"status\": \"A\",\n    \"skNumber\": \"555\\/BAN-SM\\/SK\\/2023\",\n    \"skDate\": \"25 Mei 2023\",\n    \"description\": \"SMP Negeri 1 Kotabaru memperoleh Akreditasi A berdasarkan SK No. 555\\/BAN-SM\\/SK\\/2023 tanggal 25 Mei 2023.\",\n    \"filePath\": \"\",\n    \"originalFilename\": \"\",\n    \"fileSize\": 0\n}', '2026-05-01 00:34:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `staff_members`
--

CREATE TABLE `staff_members` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `position` varchar(160) NOT NULL,
  `subject` varchar(160) NOT NULL,
  `bio` text NOT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `photo_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `staff_members`
--

INSERT INTO `staff_members` (`id`, `name`, `position`, `subject`, `bio`, `phone`, `email`, `photo_path`, `is_active`, `sort_order`, `created_at`) VALUES
(3, 'Hj.Srf.Erina Mustina,S.Pd.Ind', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 2, '2026-04-30 04:31:05'),
(4, 'H. Mahliadi, S.Pd', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 3, '2026-04-30 04:31:05'),
(5, 'Hj. Miraje Olvah, S.PdI', 'Guru', 'Seni Budaya', 'Pendidik mata pelajaran Seni Budaya.', NULL, NULL, '/public/logo-sekolah.png', 1, 4, '2026-04-30 04:31:05'),
(6, 'Tri Anjarini, S.Pd.Ing', 'Guru', 'B. Inggris', 'Pendidik mata pelajaran B. Inggris.', NULL, NULL, '/public/logo-sekolah.png', 1, 5, '2026-04-30 04:31:05'),
(8, 'Patmi, S.Pd', 'Guru', 'B. Inggris', 'Pendidik mata pelajaran B. Inggris.', NULL, NULL, '/public/logo-sekolah.png', 1, 7, '2026-04-30 04:31:05'),
(9, 'Endang Pujiwati, S.Pd', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 8, '2026-04-30 04:31:05'),
(10, 'Lilik Handayani, S.Pd', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 9, '2026-04-30 04:31:05'),
(11, 'Hj. Misnah, S.Pd.Ing', 'Guru', 'B. Inggris', 'Pendidik mata pelajaran B. Inggris.', NULL, NULL, '/public/logo-sekolah.png', 1, 10, '2026-04-30 04:31:05'),
(12, 'Hj. Ramdiana Ningsih, S.Pd', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 11, '2026-04-30 04:31:05'),
(13, 'Narita Sari, S.Pd', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 12, '2026-04-30 04:31:05'),
(14, 'Hj. Netti Rahayu, S.Sos', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 13, '2026-04-30 04:31:05'),
(15, 'Masriadi, S.Pd', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 14, '2026-04-30 04:31:05'),
(16, 'Hj. Rabiatul Adawiyah, S.Ag', 'Guru', 'PA. Islam', 'Pendidik mata pelajaran PA. Islam.', NULL, NULL, '/public/logo-sekolah.png', 1, 15, '2026-04-30 04:31:05'),
(17, 'Tiarma Pasaribu, S.Pd', 'Guru', 'Matematika', 'Pendidik mata pelajaran Matematika.', NULL, NULL, '/public/logo-sekolah.png', 1, 16, '2026-04-30 04:31:05'),
(18, 'Purnomo, S.Pd', 'Guru', 'PKN', 'Pendidik mata pelajaran PKN.', NULL, NULL, '/public/logo-sekolah.png', 1, 17, '2026-04-30 04:31:05'),
(19, 'Rina Sari, S.Pd.I', 'Guru', 'PA. Islam', 'Pendidik mata pelajaran PA. Islam.', NULL, NULL, '/public/logo-sekolah.png', 1, 18, '2026-04-30 04:31:05'),
(20, 'Idham, SE', 'Guru', 'BK', 'Pendidik layanan BK.', NULL, NULL, '/public/logo-sekolah.png', 1, 19, '2026-04-30 04:31:05'),
(22, 'Meilisda Hariyati, S.Pd', 'Guru', 'IPA', 'Pendidik mata pelajaran IPA.', NULL, NULL, '/public/logo-sekolah.png', 1, 21, '2026-04-30 04:31:05'),
(23, 'Puspa Herliana, S.Pd', 'Guru', 'Matematika', 'Pendidik mata pelajaran Matematika.', NULL, NULL, '/public/logo-sekolah.png', 1, 22, '2026-04-30 04:31:05'),
(24, 'Nur Irsyad Jabiruddin, S.Pd', 'Guru', 'PJOK', 'Pendidik mata pelajaran PJOK.', NULL, NULL, '/public/logo-sekolah.png', 1, 23, '2026-04-30 04:31:05'),
(25, 'Hariani Norlina, S.Pd, M.Pd', 'Guru', 'B. Inggris', 'Pendidik mata pelajaran B. Inggris.', NULL, NULL, '/public/logo-sekolah.png', 1, 24, '2026-04-30 04:31:05'),
(27, 'Roulina Pasaribu, S. Pd', 'Guru', 'Matematika / Informatika', 'Pendidik mata pelajaran Matematika / Informatika.', NULL, NULL, '/public/logo-sekolah.png', 1, 26, '2026-04-30 04:31:05'),
(28, 'Sap\'riadi, S.Pd', 'Guru', 'PJOK', 'Pendidik mata pelajaran PJOK.', NULL, NULL, '/public/logo-sekolah.png', 1, 27, '2026-04-30 04:31:05'),
(29, 'Hj. Marlina, S.Pd', 'Guru', 'B. Inggris / Prakarya', 'Pendidik mata pelajaran B. Inggris / Prakarya.', NULL, NULL, '/public/logo-sekolah.png', 1, 28, '2026-04-30 04:31:05'),
(30, 'Masdiana, S.Pd', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 29, '2026-04-30 04:31:05'),
(31, 'Arbainah, S.Pd', 'Guru', 'Informatika', 'Pendidik mata pelajaran Informatika.', NULL, NULL, '/public/logo-sekolah.png', 1, 30, '2026-04-30 04:31:05'),
(32, 'Sunarto, S.Pd', 'Guru', 'IPA', 'Pendidik mata pelajaran IPA.', NULL, NULL, '/public/logo-sekolah.png', 1, 31, '2026-04-30 04:31:05'),
(33, 'Yulianoor, S. Pd', 'Guru', 'BK', 'Pendidik layanan BK.', NULL, NULL, '/public/logo-sekolah.png', 1, 32, '2026-04-30 04:31:05'),
(34, 'Gusti Noor Effendy, S.Pd.I', 'Guru', 'PA. Islam', 'Pendidik mata pelajaran PA. Islam.', NULL, NULL, '/public/logo-sekolah.png', 1, 33, '2026-04-30 04:31:05'),
(35, 'Desi Fatmasari, S.Pd', 'Guru', 'IPA', 'Pendidik mata pelajaran IPA.', NULL, NULL, '/public/logo-sekolah.png', 1, 34, '2026-04-30 04:31:05'),
(36, 'Nilam Cahaya, S.Pd', 'Guru', 'PKN', 'Pendidik mata pelajaran PKN.', NULL, NULL, '/public/logo-sekolah.png', 1, 35, '2026-04-30 04:31:05'),
(37, 'Mahda Agustina Liani, S.Pd', 'Guru', 'IPA / Prakarya', 'Pendidik mata pelajaran IPA / Prakarya.', NULL, NULL, '/public/logo-sekolah.png', 1, 36, '2026-04-30 04:31:05'),
(38, 'Gusti Rusma Mulyana, S.Pd', 'Guru', 'Matematika', 'Pendidik mata pelajaran Matematika.', NULL, NULL, '/public/logo-sekolah.png', 1, 37, '2026-04-30 04:31:05'),
(39, 'Ade Noor Seprinawanti, S.Pd', 'Guru', 'Matematika', 'Pendidik mata pelajaran Matematika.', NULL, NULL, '/public/logo-sekolah.png', 1, 38, '2026-04-30 04:31:05'),
(40, 'Noor Jannah, S.Pd', 'Guru', 'Matematika', 'Pendidik mata pelajaran Matematika.', NULL, NULL, '/public/logo-sekolah.png', 1, 39, '2026-04-30 04:31:05'),
(41, 'Nurul Aisatul Hamisa, S.Pd', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 40, '2026-04-30 04:31:05'),
(42, 'Suciaty Indah Maya Sari, S.Pd', 'Guru', 'Matematika / Informatika', 'Pendidik mata pelajaran Matematika / Informatika.', NULL, NULL, '/public/logo-sekolah.png', 1, 41, '2026-04-30 04:31:05'),
(43, 'Okta Ramayanti, S.Pd', 'Guru', 'B. Indonesia / Prakarya', 'Pendidik mata pelajaran B. Indonesia / Prakarya.', NULL, NULL, '/public/logo-sekolah.png', 1, 42, '2026-04-30 04:31:05'),
(44, 'Nur Fitri Annisa, S.Pd', 'Guru', 'B. Indonesia', 'Pendidik mata pelajaran B. Indonesia.', NULL, NULL, '/public/logo-sekolah.png', 1, 43, '2026-04-30 04:31:05'),
(45, 'Rendi S, S.Pd', 'Guru', 'PJOK', 'Pendidik mata pelajaran PJOK.', NULL, NULL, '/public/logo-sekolah.png', 1, 44, '2026-04-30 04:31:05'),
(46, 'Nidasalma Rofi, S.Pd', 'Guru', 'Seni Budaya', 'Pendidik mata pelajaran Seni Budaya.', NULL, NULL, '/public/logo-sekolah.png', 1, 45, '2026-04-30 04:31:05'),
(47, 'Sylvia Widya Pratama, S.Pd', 'Guru', 'PA. Protestan', 'Pendidik mata pelajaran PA. Protestan.', NULL, NULL, '/public/logo-sekolah.png', 1, 46, '2026-04-30 04:31:05'),
(48, 'Noor Hakim Ahyar, S.Pd.I', 'Guru', 'PKN', 'Pendidik mata pelajaran PKN.', NULL, NULL, '/public/logo-sekolah.png', 1, 47, '2026-04-30 04:31:05'),
(49, 'Noor Intan Sari S.Pd', 'Guru', 'B. Inggris', 'Pendidik mata pelajaran B. Inggris.', NULL, NULL, '/public/logo-sekolah.png', 1, 48, '2026-04-30 04:31:05'),
(50, 'Wanda Yustryana, S.Pd', 'Guru', 'PKN', 'Pendidik mata pelajaran PKN.', NULL, NULL, '/public/logo-sekolah.png', 1, 49, '2026-04-30 04:31:05'),
(51, 'Restya Dwi Amalia, M.Pd', 'Guru', 'IPS', 'Pendidik mata pelajaran IPS.', NULL, NULL, '/public/logo-sekolah.png', 1, 50, '2026-04-30 04:31:05'),
(52, 'Jumiatul, S.Pd', 'Guru', 'Inklusi', 'Pendidik program inklusi.', NULL, NULL, '/public/logo-sekolah.png', 1, 51, '2026-04-30 04:31:05'),
(53, 'Noor Rifanni, S. Pd', 'Guru', 'Seni Budaya', 'Pendidik mata pelajaran Seni Budaya.', NULL, NULL, '/public/logo-sekolah.png', 1, 52, '2026-04-30 04:31:05'),
(54, 'Muhammad Irfani, S.Pd', 'Guru', 'PKN', 'Pendidik mata pelajaran PKN.', NULL, NULL, '/public/logo-sekolah.png', 1, 53, '2026-04-30 04:31:05'),
(55, 'Wahida Nur, S. Pd', 'Guru', 'Seni Budaya', 'Pendidik mata pelajaran Seni Budaya.', NULL, NULL, '/public/logo-sekolah.png', 1, 54, '2026-04-30 04:31:05'),
(56, 'Luthfia Azizah, S. Pd', 'Guru', 'BK', 'Pendidik layanan BK.', NULL, NULL, '/public/logo-sekolah.png', 1, 55, '2026-04-30 04:31:05'),
(57, 'Ayu Nurmiyanti, S. Pd', 'Guru', 'Seni Budaya', 'Pendidik mata pelajaran Seni Budaya.', NULL, NULL, '/public/logo-sekolah.png', 1, 56, '2026-04-30 04:31:05'),
(58, 'Siti Hafsah, A.Md', 'Tenaga Administrasi', '-', 'Tenaga administrasi sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 57, '2026-04-30 04:31:05'),
(59, 'Maulidatul Husna, S.Pd', 'Tenaga Administrasi', '-', 'Tenaga administrasi sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 58, '2026-04-30 04:31:05'),
(60, 'Muhammad Reivei Saubari', 'Tenaga Administrasi', '-', 'Tenaga administrasi sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 59, '2026-04-30 04:31:05'),
(61, 'Ananda Nicola, S. IP', 'Pustakawan', '-', 'Tenaga perpustakaan sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 60, '2026-04-30 04:31:05'),
(62, 'Siti Maryam', 'Pesuruh / OB', '-', 'Tenaga layanan kebersihan dan operasional sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 61, '2026-04-30 04:31:05'),
(63, 'Kursani', 'Pesuruh / OB', '-', 'Tenaga layanan kebersihan dan operasional sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 62, '2026-04-30 04:31:05'),
(64, 'Irna Sugiarti', 'Pesuruh / OB', '-', 'Tenaga layanan kebersihan dan operasional sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 63, '2026-04-30 04:31:05'),
(65, 'Taufikurrakhman', 'Keamanan', '-', 'Tenaga keamanan sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 64, '2026-04-30 04:31:05'),
(66, 'Marhan', 'Keamanan', '-', 'Tenaga keamanan sekolah.', NULL, NULL, '/public/logo-sekolah.png', 1, 65, '2026-04-30 04:31:05'),
(67, 'Rusdiansyah, S.Pd', 'Kepala Sekolah', 'Kepala Sekolah', 'Beliau dipercaya mengemban amanah sebagai nakhoda di SMP Negeri 1 Kotabaru dengan membawa semangat transformasi pendidikan yang inklusif dan progresif. Sebagai seorang pendidik yang memiliki dedikasi tinggi, beliau percaya bahwa sekolah bukan hanya tempat mengejar nilai akademik, melainkan sebuah \"Rumah Belajar\" tempat setiap anak merasa aman untuk tumbuh dan berani untuk berekspresi.\r\n\r\nDalam kepemimpinannya, beliau fokus pada penguatan karakter siswa yang berlandaskan iman dan akhlak mulia, serta mendorong integrasi teknologi dalam pembelajaran. Beliau berkomitmen untuk:\r\n\r\nMenciptakan Sekolah Ramah Anak: Memastikan SMPN 1 Kotabaru menjadi lingkungan yang bebas dari perundungan dan penuh kasih sayang.\r\n\r\nMendorong Literasi & Inovasi: Mendukung penuh pengembangan bakat siswa, mulai dari bidang jurnalistik hingga penguasaan teknologi digital.\r\n\r\nPelestarian Budaya: Menanamkan kebanggaan pada akar budaya lokal sebagai identitas diri di tengah arus globalisasi.\r\n\r\nBagi beliau, setiap siswa memiliki potensi unik. Melalui semangat kolaborasi antara guru, orang tua, dan masyarakat, beliau berupaya membawa SMP Negeri 1 Kotabaru menjadi institusi pendidikan yang unggul, inspiratif, dan berorientasi pada keberlanjutan.\r\n\r\n\"Pendidikan adalah perjalanan untuk menemukan diri sendiri. Mari kita bimbing anak-anak kita untuk pulang ke jati dirinya, menjadi pribadi yang kritis, kreatif, dan memiliki empati terhadap sesama.\"', '-', 'rusdiansyah@gmail.com', '/public/uploads/staff/staff-20260430080936-c0001722902430fd.webp', 1, 1, '2026-04-30 06:09:37');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `agenda_items`
--
ALTER TABLE `agenda_items`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `extracurriculars`
--
ALTER TABLE `extracurriculars`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `gallery_items`
--
ALTER TABLE `gallery_items`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `ppdb_applications`
--
ALTER TABLE `ppdb_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`);

--
-- Indeks untuk tabel `ppdb_entries`
--
ALTER TABLE `ppdb_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `school_achievements`
--
ALTER TABLE `school_achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `school_documents`
--
ALTER TABLE `school_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `school_facilities`
--
ALTER TABLE `school_facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `staff_members`
--
ALTER TABLE `staff_members`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `agenda_items`
--
ALTER TABLE `agenda_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `extracurriculars`
--
ALTER TABLE `extracurriculars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `gallery_items`
--
ALTER TABLE `gallery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT untuk tabel `ppdb_applications`
--
ALTER TABLE `ppdb_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `ppdb_entries`
--
ALTER TABLE `ppdb_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `school_achievements`
--
ALTER TABLE `school_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `school_documents`
--
ALTER TABLE `school_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `school_facilities`
--
ALTER TABLE `school_facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=423;

--
-- AUTO_INCREMENT untuk tabel `staff_members`
--
ALTER TABLE `staff_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
