-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: website_sekolah_test_stage3
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `role` enum('superadmin','editor','ppdb') NOT NULL DEFAULT 'editor',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=841 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (840,'review_local_1010232637','Admin Review Lokal Testing','superadmin',1,'$2y$10$KNspav28LpBfaE9YBudfPebkOHTvzpe0vQ/vYhQtgj3CuQS49p4I.','2026-10-10 15:26:37');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agenda_items`
--

DROP TABLE IF EXISTS `agenda_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agenda_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026',
  `title` varchar(255) NOT NULL,
  `category` varchar(120) NOT NULL,
  `location` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agenda_items`
--

LOCK TABLES `agenda_items` WRITE;
/*!40000 ALTER TABLE `agenda_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `agenda_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `link_label` varchar(120) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(60) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `extracurriculars`
--

DROP TABLE IF EXISTS `extracurriculars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extracurriculars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL,
  `coach` varchar(160) DEFAULT NULL,
  `schedule_text` varchar(180) DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=407 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `extracurriculars`
--

LOCK TABLES `extracurriculars` WRITE;
/*!40000 ALTER TABLE `extracurriculars` DISABLE KEYS */;
/*!40000 ALTER TABLE `extracurriculars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_items`
--

DROP TABLE IF EXISTS `gallery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=468 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_items`
--

LOCK TABLES `gallery_items` WRITE;
/*!40000 ALTER TABLE `gallery_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `gallery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(180) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `published_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=485 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ppdb_applications`
--

DROP TABLE IF EXISTS `ppdb_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppdb_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026',
  `status` enum('baru','diverifikasi','diterima','ditolak') NOT NULL DEFAULT 'baru',
  `family_card_path` varchar(255) DEFAULT NULL,
  `birth_certificate_path` varchar(255) DEFAULT NULL,
  `report_card_path` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ppdb_applications`
--

LOCK TABLES `ppdb_applications` WRITE;
/*!40000 ALTER TABLE `ppdb_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `ppdb_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ppdb_entries`
--

DROP TABLE IF EXISTS `ppdb_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppdb_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ppdb_entries`
--

LOCK TABLES `ppdb_entries` WRITE;
/*!40000 ALTER TABLE `ppdb_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `ppdb_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_achievements`
--

DROP TABLE IF EXISTS `school_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026',
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=439 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_achievements`
--

LOCK TABLES `school_achievements` WRITE;
/*!40000 ALTER TABLE `school_achievements` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_documents`
--

DROP TABLE IF EXISTS `school_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL DEFAULT '2025/2026',
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=506 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_documents`
--

LOCK TABLES `school_documents` WRITE;
/*!40000 ALTER TABLE `school_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_facilities`
--

DROP TABLE IF EXISTS `school_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) NOT NULL,
  `location` varchar(180) DEFAULT NULL,
  `capacity` varchar(120) DEFAULT NULL,
  `condition_status` varchar(120) NOT NULL DEFAULT 'Baik',
  `description` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_facilities`
--

LOCK TABLES `school_facilities` WRITE;
/*!40000 ALTER TABLE `school_facilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=12658 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (11090,'schoolProfile','{\n    \"name\": \"SMP Negeri 1 Kotabaru\",\n    \"shortName\": \"SMPN 1 Kotabaru\",\n    \"tagline\": \"Sekolah Aktif, Berkarakter, dan Berprestasi\",\n    \"description\": \"Website resmi sekolah sebagai sarana publikasi, layanan informasi, dan komunikasi kelembagaan yang mendukung transformasi pembelajaran, budaya kolaboratif, serta peningkatan mutu pendidikan.\",\n    \"heroDescription\": \"Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.\",\n    \"address\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\",\n    \"phone\": \"(0518) 21602\",\n    \"email\": \"smpn1_54ktb@yahoo.co.id\",\n    \"serviceHours\": \"Senin-Jumat, 07.00-15.00\",\n    \"logoPath\": \"\\/public\\/logo-sekolah.png\"\n}','2026-10-10 15:32:48'),(11091,'siteMaintenance','{\n    \"enabled\": false,\n    \"message\": \"Website sekolah sedang dalam pemeliharaan singkat. Silakan kembali beberapa saat lagi.\"\n}','2026-10-10 15:34:09'),(11092,'siteTheme','{\n    \"primaryColor\": \"#0284c7\",\n    \"primaryDarkColor\": \"#0b4fa8\",\n    \"secondaryColor\": \"#bef4fe\",\n    \"accentColor\": \"#eab308\",\n    \"darkColor\": \"#0f172a\"\n}','2026-10-10 16:13:56'),(11093,'siteAcademic','{\n    \"currentYear\": \"2026\\/2027\"\n}','2026-10-12 23:01:08'),(11094,'siteHome','{\n    \"heroTitle\": \"SMP Negeri 1 Kotabaru\",\n    \"heroSubtitle\": \"Media informasi resmi SMP Negeri 1 Kotabaru yang memuat profil sekolah, layanan akademik, kegiatan kesiswaan, publikasi, serta arah pengembangan sekolah yang adaptif, reflektif, dan berorientasi pada pembelajaran mendalam.\",\n    \"primaryButtonLabel\": \"Lihat Profil Sekolah\",\n    \"primaryButtonUrl\": \"\\/profil\",\n    \"secondaryButtonLabel\": \"Informasi PPDB\",\n    \"secondaryButtonUrl\": \"\\/ppdb\",\n    \"profileVideoUrl\": \"https:\\/\\/www.youtube.com\\/watch?v=bbWLfO9AE6M\",\n    \"profileVideoTitle\": \"Profil sekolah dalam satu tayangan\",\n    \"profileVideoDescription\": \"Ruang ini disiapkan untuk menampilkan video profil resmi sekolah, mulai dari lingkungan belajar, kegiatan siswa, fasilitas, hingga budaya sekolah.\",\n    \"teacherLimit\": 12,\n    \"agendaLimit\": 3,\n    \"showAnnouncements\": true,\n    \"showQuickLinks\": true,\n    \"showLogoCard\": true,\n    \"showStats\": true,\n    \"showPrincipalCard\": true,\n    \"showTeacherCarousel\": true,\n    \"showProfileIntro\": true,\n    \"showFeaturedAreas\": true,\n    \"showAgenda\": true,\n    \"profileVideoSource\": \"youtube\"\n}','2026-10-12 15:38:49'),(11095,'principalMessage','{\n    \"name\": \"Rusdiansyah, S.Pd.\",\n    \"title\": \"Kepala Sekolah\",\n    \"greeting\": \"Assalamu\'alaikum warahmatullahi wabarakatuh. Selamat datang di website resmi SMP Negeri 1 Kotabaru.\",\n    \"photoPath\": \"\\/public\\/kepala-sekolah.jpeg\",\n    \"paragraphs\": [\n        \"Website ini kami hadirkan sebagai media informasi resmi sekolah yang dapat mempererat komunikasi antara sekolah, peserta didik, orang tua, dan masyarakat.\",\n        \"SMP Negeri 1 Kotabaru berkomitmen memperkuat pembelajaran mendalam melalui budaya reflektif, kolaboratif, adaptif, dan berpusat pada kebutuhan peserta didik.\",\n        \"Kami terus mendorong guru, tenaga kependidikan, peserta didik, orang tua, komite sekolah, dan mitra eksternal untuk terlibat dalam peningkatan mutu pendidikan secara berkelanjutan.\"\n    ]\n}','2026-10-10 15:32:48'),(11096,'navigation','[\n    {\n        \"href\": \"\\/\",\n        \"label\": \"Beranda\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/profil\",\n        \"label\": \"Profil\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/fasilitas\",\n        \"label\": \"Fasilitas\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/akademik\",\n        \"label\": \"Akademik\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/agenda\",\n        \"label\": \"Agenda\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/guru-staf\",\n        \"label\": \"Guru & Staf\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/dokumen\",\n        \"label\": \"Dokumen\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/prestasi\",\n        \"label\": \"Prestasi\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/kesiswaan\",\n        \"label\": \"Kesiswaan\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/ekstrakurikuler\",\n        \"label\": \"Ekskul\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/berita\",\n        \"label\": \"Berita\",\n        \"visible\": true\n    },\n    {\n        \"href\": \"\\/galeri\",\n        \"label\": \"Galeri\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/ppdb\",\n        \"label\": \"PPDB\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/faq\",\n        \"label\": \"FAQ\",\n        \"visible\": false\n    },\n    {\n        \"href\": \"\\/kontak\",\n        \"label\": \"Kontak\",\n        \"visible\": false\n    }\n]','2026-10-12 23:02:10'),(11097,'stats','[\n    {\n        \"label\": \"Siswa\",\n        \"value\": \"775\"\n    },\n    {\n        \"label\": \"Guru & Staf\",\n        \"value\": \"65\"\n    },\n    {\n        \"label\": \"Ekstrakurikuler\",\n        \"value\": \"19\"\n    },\n    {\n        \"label\": \"Rombel\",\n        \"value\": \"24\"\n    }\n]','2026-10-10 15:32:48'),(11098,'quickLinks','[\n    {\n        \"title\": \"Profil Sekolah\",\n        \"desc\": \"Sejarah singkat, visi, misi, dan gambaran umum penyelenggaraan pendidikan di sekolah.\",\n        \"href\": \"\\/profil\"\n    },\n    {\n        \"title\": \"Fasilitas Sekolah\",\n        \"desc\": \"Ruang belajar, laboratorium, perpustakaan, lapangan, dan sarana pendukung kegiatan sekolah.\",\n        \"href\": \"\\/fasilitas\"\n    },\n    {\n        \"title\": \"Agenda Akademik\",\n        \"desc\": \"Kalender pendidikan, asesmen, dan jadwal kegiatan akademik penting.\",\n        \"href\": \"\\/akademik\"\n    },\n    {\n        \"title\": \"Ekstrakurikuler\",\n        \"desc\": \"Program pengembangan minat, bakat, dan pembinaan karakter peserta didik.\",\n        \"href\": \"\\/ekstrakurikuler\"\n    },\n    {\n        \"title\": \"Informasi PPDB\",\n        \"desc\": \"Alur, persyaratan, dan jadwal penerimaan peserta didik baru.\",\n        \"href\": \"\\/ppdb\"\n    }\n]','2026-10-10 15:32:48'),(11099,'featuredAreas','[\n    {\n        \"title\": \"Akademik\",\n        \"desc\": \"Informasi pembelajaran, asesmen, agenda akademik, dan layanan pendidikan sekolah.\",\n        \"href\": \"\\/akademik\"\n    },\n    {\n        \"title\": \"Kesiswaan\",\n        \"desc\": \"Informasi organisasi siswa, ekstrakurikuler, pembinaan karakter, dan prestasi peserta didik.\",\n        \"href\": \"\\/kesiswaan\"\n    },\n    {\n        \"title\": \"Publikasi\",\n        \"desc\": \"Berita sekolah, dokumentasi kegiatan, galeri, dan informasi publik resmi sekolah.\",\n        \"href\": \"\\/berita\"\n    }\n]','2026-10-10 15:32:48'),(11100,'excellencePrograms','[\n    \"Pelaksanaan pembelajaran berbasis eksplorasi, refleksi, proyek, dan kolaborasi.\",\n    \"Penguatan budaya inkuiri kolaboratif melalui keterlibatan guru, peserta didik, orang tua, komite, dan mitra sekolah.\",\n    \"Pengembangan pembelajaran berbasis budaya lokal, teknologi, dan konteks kehidupan nyata peserta didik.\",\n    \"Penguatan komunitas belajar guru melalui forum diskusi, refleksi praktik, dan berbagi praktik baik.\"\n]','2026-10-10 15:32:48'),(11101,'profileSections','[\n    {\n        \"title\": \"Sejarah Singkat\",\n        \"text\": \"SMP Negeri 1 Kotabaru merupakan institusi pendidikan yang berkomitmen terhadap transformasi mutu pembelajaran melalui penguatan kepemimpinan pembelajaran, budaya kolaboratif, dan pengambilan keputusan berbasis data. Sekolah mendorong proses pendidikan yang adaptif, inklusif, reflektif, serta relevan dengan kebutuhan peserta didik dan tantangan abad ke-21.\"\n    },\n    {\n        \"title\": \"Visi\",\n        \"text\": \"Mewujudkan peserta didik yang beriman dan berakhlak mulia, berpikir kritis, kreatif, dan reflektif, serta mampu berinovasi dan berkolaborasi dalam teknologi, berakar pada budaya lokal, dan berorientasi pada keberlanjutan.\"\n    },\n    {\n        \"title\": \"Misi\",\n        \"text\": \"1. Merancang pembelajaran berbasis eksplorasi, refleksi, dan kolaborasi. 2. Mengintegrasikan pembelajaran berbasis proyek dan budaya lokal. 3. Meningkatkan manajemen pendidikan yang adaptif dan ramah anak melalui kebijakan pembelajaran yang mendorong otonomi profesional guru serta partisipasi aktif murid. 4. Merancang strategi pembelajaran diferensiatif dan berbasis kekuatan murid. 5. Menjalin kemitraan pembelajaran berbasis komunitas dan CSR. 6. Menyusun kurikulum yang menumbuhkan pemikiran reflektif dan inovatif. 7. Mendorong guru sebagai fasilitator pembelajaran mendalam melalui refleksi dan praktik inovatif. 8. Menjadikan sekolah sebagai komunitas pembelajar yang adaptif dan berkelanjutan.\"\n    }\n]','2026-10-10 15:32:48'),(11102,'schoolFacilities','[\n    \"24 ruang teori\\/kelas\",\n    \"Laboratorium IPA\",\n    \"Laboratorium Fisika\",\n    \"2 laboratorium bahasa\",\n    \"Laboratorium komputer\",\n    \"Ruang perpustakaan\",\n    \"Ruang keterampilan\",\n    \"Ruang serba guna\",\n    \"Ruang UKS\",\n    \"Ruang BP\\/BK\",\n    \"Ruang guru\",\n    \"Ruang OSIS\",\n    \"Kamar mandi\\/WC guru dan siswa\",\n    \"Gudang\",\n    \"Ruang ibadah\"\n]','2026-10-10 15:32:48'),(11103,'schoolIdentity','[\n    {\n        \"label\": \"Nama Sekolah\",\n        \"value\": \"SMP Negeri 1 Kotabaru\"\n    },\n    {\n        \"label\": \"Status\",\n        \"value\": \"Sekolah Menengah Pertama Negeri\"\n    },\n    {\n        \"label\": \"NPSN\",\n        \"value\": \"30303355\"\n    },\n    {\n        \"label\": \"NSS\",\n        \"value\": \"201150901001\"\n    },\n    {\n        \"label\": \"Alamat\",\n        \"value\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\"\n    },\n    {\n        \"label\": \"Akreditasi\",\n        \"value\": \"A - SK No. 555\\/BAN-SM\\/SK\\/2023 tanggal 25 Mei 2023\"\n    },\n    {\n        \"label\": \"Tahun Berdiri\",\n        \"value\": \"1954\"\n    },\n    {\n        \"label\": \"Jam Layanan\",\n        \"value\": \"Senin-Jumat, 07.00-15.00\"\n    },\n    {\n        \"label\": \"Email Resmi\",\n        \"value\": \"smpn1_54ktb@yahoo.co.id\"\n    },\n    {\n        \"label\": \"Telepon\",\n        \"value\": \"(0518) 21602\"\n    }\n]','2026-10-10 15:32:48'),(11104,'schoolAccreditation','{\n    \"status\": \"A\",\n    \"skNumber\": \"555\\/BAN-SM\\/SK\\/2023\",\n    \"skDate\": \"25 Mei 2023\",\n    \"description\": \"SMP Negeri 1 Kotabaru memperoleh Akreditasi A berdasarkan SK No. 555\\/BAN-SM\\/SK\\/2023 tanggal 25 Mei 2023.\",\n    \"filePath\": \"\",\n    \"originalFilename\": \"\",\n    \"fileSize\": 0\n}','2026-10-10 15:32:48'),(11105,'contactChannels','[\n    {\n        \"title\": \"Alamat Sekolah\",\n        \"value\": \"Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117\",\n        \"desc\": \"Alamat resmi sekolah sesuai laporan bulanan sekolah.\"\n    },\n    {\n        \"title\": \"Telepon\",\n        \"value\": \"(0518) 21602\",\n        \"desc\": \"Nomor telepon resmi sekolah yang dapat dihubungi pada jam layanan administrasi.\"\n    },\n    {\n        \"title\": \"Email Resmi\",\n        \"value\": \"smpn1_54ktb@yahoo.co.id\",\n        \"desc\": \"Alamat surat elektronik resmi sekolah untuk layanan informasi dan korespondensi.\"\n    },\n    {\n        \"title\": \"Jam Layanan\",\n        \"value\": \"Senin-Jumat, 07.00-15.00\",\n        \"desc\": \"Jam layanan sekolah untuk administrasi, komunikasi orang tua, dan keperluan informasi umum.\"\n    }\n]','2026-10-10 15:32:48');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_members`
--

DROP TABLE IF EXISTS `staff_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `position` varchar(160) NOT NULL,
  `subject` varchar(160) NOT NULL,
  `bio` text NOT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `photo_path` varchar(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=415 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_members`
--

LOCK TABLES `staff_members` WRITE;
/*!40000 ALTER TABLE `staff_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stage3_test_environment`
--

DROP TABLE IF EXISTS `stage3_test_environment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stage3_test_environment` (
  `id` tinyint(4) NOT NULL,
  `marker` char(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stage3_test_environment`
--

LOCK TABLES `stage3_test_environment` WRITE;
/*!40000 ALTER TABLE `stage3_test_environment` DISABLE KEYS */;
INSERT INTO `stage3_test_environment` VALUES (1,'c2cf83397dc81ba71e416c8899983709e9d90cae8c7888b78d843b73e2331887');
/*!40000 ALTER TABLE `stage3_test_environment` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-15 18:17:50
