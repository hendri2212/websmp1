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
-- Struktur dari tabel `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `role` varchar(40) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `module` varchar(80) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `username`, `role`, `action`, `module`, `entity_type`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-29 11:08:44'),
(2, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-29 11:15:40'),
(3, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 4, 'Akun admin dibuat: AdminTU', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-29 11:19:25'),
(4, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-29 11:20:19'),
(5, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 12:45:51'),
(6, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 12:57:24'),
(7, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 12:57:36'),
(8, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', NULL, NULL, '2026-04-29 13:00:52'),
(9, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', NULL, NULL, '2026-04-29 13:07:43'),
(10, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, NULL, NULL, '2026-04-29 13:09:14'),
(11, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:09:31'),
(12, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:09:45'),
(13, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:02'),
(14, 1, 'admin', 'superadmin', 'export', 'ppdb_applications', 'ppdb_application', NULL, 'Data pendaftar PPDB diexport ke CSV.', '{\"filename\":\"pendaftar-ppdb-20260429-151002.csv\",\"row_count\":1,\"filters\":{\"q\":\"\",\"status\":\"\"}}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:02'),
(15, 1, 'admin', 'superadmin', 'export', 'db_backup', 'database', NULL, 'Backup database diunduh.', '{\"database\":\"website_sekolah\",\"filename\":\"backup-website_sekolah-20260429-151002.sql\",\"tables\":16}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:02'),
(16, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:18'),
(17, 1, 'admin', 'superadmin', 'export', 'db_backup', 'database', NULL, 'Backup database diunduh.', '{\"database\":\"website_sekolah\",\"filename\":\"backup-website_sekolah-20260429-151018.sql\",\"tables\":16}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:18'),
(18, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:32'),
(19, 1, 'admin', 'superadmin', 'export', 'db_backup', 'database', NULL, 'Backup database diunduh.', '{\"database\":\"website_sekolah\",\"filename\":\"backup-website_sekolah-20260429-151032.sql\",\"tables\":16}', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:10:32'),
(20, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:11:01'),
(21, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 0, 'Berita dihapus: ID 0', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:11:03'),
(22, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:11:22'),
(23, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:11:38'),
(24, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:11:50'),
(25, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:13:32'),
(26, 1, 'admin', 'superadmin', 'create', 'news', 'news', 1, 'Berita dibuat: TEST E2E Berita 20260429211332', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:13:32'),
(27, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 1, 'Berita dihapus: TEST E2E Berita 20260429211332', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:13:32'),
(28, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 1, 'Galeri dibuat: TEST E2E Galeri 20260429211332', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:13:32'),
(29, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 1, 'Galeri dihapus: TEST E2E Galeri 20260429211332', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:13:32'),
(30, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.7920', '2026-04-29 13:41:47'),
(31, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', NULL, '2026-04-29 13:53:46'),
(32, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', NULL, '2026-04-29 13:54:18'),
(33, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 2, 'Galeri dibuat: TEST GD WEBP 20260429215418', NULL, '::1', NULL, '2026-04-29 13:54:18'),
(34, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', NULL, '2026-04-29 13:55:44'),
(35, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 2, 'Galeri dihapus: TEST GD WEBP 20260429215418', NULL, '::1', NULL, '2026-04-29 13:55:44'),
(36, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:02:54'),
(37, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:08:00'),
(38, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:09:09'),
(39, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:15:29'),
(40, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:18:27'),
(41, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:19:22'),
(42, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:24:12'),
(43, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 2, 'Ekstrakurikuler dibuat: Jurnalistik', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:28:54'),
(44, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:33:53'),
(45, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:38:20'),
(46, 1, 'admin', 'superadmin', 'update', 'extracurriculars', 'extracurricular', 2, 'Ekstrakurikuler diperbarui: Jurnalistik', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:40:49'),
(47, 1, 'admin', 'superadmin', 'create', 'news', 'news', 2, 'Berita dibuat: Sekolah Ramah Anak', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:44:30'),
(48, 1, 'admin', 'superadmin', 'update', 'news', 'news', 2, 'Berita diperbarui: Spensa Update', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:45:46'),
(49, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 2, 'Agenda dibuat: Berdirinya SMP Negeri 1 Kotabaru', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 01:51:19'),
(50, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 02:18:52'),
(51, 1, 'admin', 'superadmin', 'update', 'agenda', 'agenda_item', 2, 'Agenda diperbarui: 71th SMP Negeri 1 Kotabaru', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 02:20:48'),
(52, NULL, NULL, NULL, 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, NULL, NULL, '2026-04-30 04:21:02'),
(53, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 04:35:10'),
(54, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 06:03:30'),
(55, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 67, 'Data guru/staf dibuat: Rusdiansyah, S.Pd', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 06:09:37'),
(56, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 2, 'Data guru/staf dihapus: Rusdiansyah, S.Pd', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 06:09:54'),
(57, 1, 'admin', 'superadmin', 'update', 'staff', 'staff_member', 67, 'Data guru/staf diperbarui: Rusdiansyah, S.Pd', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 06:10:34'),
(58, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 5, 'Akun admin dibuat: smoke-20260430160159-editor', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(59, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 5, 'Akun admin dihapus: smoke-20260430160159-editor', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(60, 1, 'admin', 'superadmin', 'create', 'news', 'news', 3, 'Berita dibuat: SMOKE-20260430160159-Berita', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(61, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 3, 'Berita dihapus: SMOKE-20260430160159-Berita', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(62, 1, 'admin', 'superadmin', 'create', 'news', 'news', 4, 'Berita dibuat: SMOKE-20260430160159-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(63, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 3, 'Galeri dibuat: SMOKE-20260430160159-Galeri', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(64, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 3, 'Galeri dihapus: SMOKE-20260430160159-Galeri', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(65, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 3, 'Pengumuman dibuat: SMOKE-20260430160159-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(66, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 3, 'Pengumuman dihapus: SMOKE-20260430160159-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(67, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 68, 'Data guru/staf dibuat: SMOKE-20260430160159-Guru', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(68, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 68, 'Data guru/staf dihapus: SMOKE-20260430160159-Guru', NULL, NULL, NULL, '2026-04-30 14:01:59'),
(69, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 3, 'Agenda dibuat: SMOKE-20260430160159-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(70, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 3, 'Agenda dihapus: SMOKE-20260430160159-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(71, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 2, 'Dokumen dibuat: SMOKE-20260430160159-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(72, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 2, 'Dokumen dihapus: SMOKE-20260430160159-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(73, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 2, 'Prestasi dibuat: SMOKE-20260430160159-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(74, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 2, 'Prestasi dihapus: SMOKE-20260430160159-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(75, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 3, 'Ekstrakurikuler dibuat: SMOKE-20260430160159-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(76, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 3, 'Ekstrakurikuler dihapus: SMOKE-20260430160159-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(77, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 18, 'Fasilitas dibuat: SMOKE-20260430160159-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(78, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 18, 'Fasilitas dihapus: SMOKE-20260430160159-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(79, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 2, 'FAQ dibuat: SMOKE-20260430160159-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(80, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 2, 'FAQ dihapus: SMOKE-20260430160159-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(81, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 1, 'Konten PPDB dibuat: SMOKE-20260430160159-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(82, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 1, 'Konten PPDB dihapus: SMOKE-20260430160159-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(83, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 2, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(84, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 2, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(85, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 4, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:02:00'),
(86, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 4, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:02:00'),
(87, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 6, 'Akun admin dibuat: smoke-20260430160211-editor', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(88, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 6, 'Akun admin dihapus: smoke-20260430160211-editor', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(89, 1, 'admin', 'superadmin', 'create', 'news', 'news', 5, 'Berita dibuat: SMOKE-20260430160211-Berita', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(90, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 5, 'Berita dihapus: SMOKE-20260430160211-Berita', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(91, 1, 'admin', 'superadmin', 'create', 'news', 'news', 6, 'Berita dibuat: SMOKE-20260430160211-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(92, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 4, 'Galeri dibuat: SMOKE-20260430160211-Galeri', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(93, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 4, 'Galeri dihapus: SMOKE-20260430160211-Galeri', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(94, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 4, 'Pengumuman dibuat: SMOKE-20260430160211-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(95, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 4, 'Pengumuman dihapus: SMOKE-20260430160211-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(96, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 69, 'Data guru/staf dibuat: SMOKE-20260430160211-Guru', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(97, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 69, 'Data guru/staf dihapus: SMOKE-20260430160211-Guru', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(98, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 4, 'Agenda dibuat: SMOKE-20260430160211-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(99, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 4, 'Agenda dihapus: SMOKE-20260430160211-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(100, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 3, 'Dokumen dibuat: SMOKE-20260430160211-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(101, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 3, 'Dokumen dihapus: SMOKE-20260430160211-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(102, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 3, 'Prestasi dibuat: SMOKE-20260430160211-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(103, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 3, 'Prestasi dihapus: SMOKE-20260430160211-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(104, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 4, 'Ekstrakurikuler dibuat: SMOKE-20260430160211-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(105, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 4, 'Ekstrakurikuler dihapus: SMOKE-20260430160211-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(106, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 19, 'Fasilitas dibuat: SMOKE-20260430160211-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(107, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 19, 'Fasilitas dihapus: SMOKE-20260430160211-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(108, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 3, 'FAQ dibuat: SMOKE-20260430160211-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(109, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 3, 'FAQ dihapus: SMOKE-20260430160211-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(110, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 2, 'Konten PPDB dibuat: SMOKE-20260430160211-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(111, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 2, 'Konten PPDB dihapus: SMOKE-20260430160211-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(112, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 3, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(113, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 3, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(114, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 5, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:02:11'),
(115, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 5, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:02:11'),
(116, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 7, 'Akun admin dibuat: smoke-20260430160232-editor', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(117, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 7, 'Akun admin dihapus: smoke-20260430160232-editor', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(118, 1, 'admin', 'superadmin', 'create', 'news', 'news', 7, 'Berita dibuat: SMOKE-20260430160232-Berita', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(119, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 7, 'Berita dihapus: SMOKE-20260430160232-Berita', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(120, 1, 'admin', 'superadmin', 'create', 'news', 'news', 8, 'Berita dibuat: SMOKE-20260430160232-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(121, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 5, 'Galeri dibuat: SMOKE-20260430160232-Galeri', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(122, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 5, 'Galeri dihapus: SMOKE-20260430160232-Galeri', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(123, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 5, 'Pengumuman dibuat: SMOKE-20260430160232-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(124, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 5, 'Pengumuman dihapus: SMOKE-20260430160232-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(125, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 70, 'Data guru/staf dibuat: SMOKE-20260430160232-Guru', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(126, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 70, 'Data guru/staf dihapus: SMOKE-20260430160232-Guru', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(127, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 5, 'Agenda dibuat: SMOKE-20260430160232-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(128, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 5, 'Agenda dihapus: SMOKE-20260430160232-Agenda', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(129, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 4, 'Dokumen dibuat: SMOKE-20260430160232-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(130, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 4, 'Dokumen dihapus: SMOKE-20260430160232-Dokumen', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(131, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 4, 'Prestasi dibuat: SMOKE-20260430160232-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(132, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 4, 'Prestasi dihapus: SMOKE-20260430160232-Prestasi', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(133, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 5, 'Ekstrakurikuler dibuat: SMOKE-20260430160232-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(134, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 5, 'Ekstrakurikuler dihapus: SMOKE-20260430160232-Ekskul', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(135, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 20, 'Fasilitas dibuat: SMOKE-20260430160232-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:32'),
(136, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 20, 'Fasilitas dihapus: SMOKE-20260430160232-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(137, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 4, 'FAQ dibuat: SMOKE-20260430160232-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(138, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 4, 'FAQ dihapus: SMOKE-20260430160232-FAQ?', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(139, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 3, 'Konten PPDB dibuat: SMOKE-20260430160232-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(140, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 3, 'Konten PPDB dihapus: SMOKE-20260430160232-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(141, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 4, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(142, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 4, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(143, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 6, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:02:33'),
(144, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 6, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:02:33'),
(145, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(146, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 6, 'Pengumuman dibuat: Layanan Informasi Sekolah Tahun Pelajaran 2025/2026', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(147, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 7, 'Pengumuman dibuat: Penguatan Pembelajaran Mendalam', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(148, 1, 'admin', 'superadmin', 'create', 'news', 'news', 9, 'Berita dibuat: Program Pembelajaran Mendalam SMP Negeri 1 Kotabaru', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(149, 1, 'admin', 'superadmin', 'create', 'news', 'news', 10, 'Berita dibuat: Profil Data Sekolah Desember 2025', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(150, 1, 'admin', 'superadmin', 'create', 'news', 'news', 11, 'Berita dibuat: Tes Kemampuan Akademik Berjalan Lancar', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(151, 1, 'admin', 'superadmin', 'create', 'news', 'news', 12, 'Berita dibuat: Ekstrakurikuler Jurnalistik Aktif Berkarya', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(152, 1, 'admin', 'superadmin', 'create', 'news', 'news', 13, 'Berita dibuat: Persiapan Video Profil Sekolah', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(153, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 6, 'Galeri dibuat: Kepala Sekolah', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(154, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 7, 'Galeri dibuat: Logo Sekolah', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(155, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 6, 'Agenda dibuat: Sosialisasi Program Semester Genap', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(156, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 7, 'Agenda dibuat: Pekan Literasi dan Numerasi', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(157, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 8, 'Agenda dibuat: Seleksi Internal Lomba Siswa', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(158, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 5, 'Prestasi dibuat: Akreditasi A SMP Negeri 1 Kotabaru', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(159, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 6, 'Prestasi dibuat: Pengembangan Program Pembelajaran Mendalam', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(160, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 6, 'Ekstrakurikuler dibuat: Pramuka', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(161, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 7, 'Ekstrakurikuler dibuat: PMR', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(162, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 8, 'Ekstrakurikuler dibuat: Futsal', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(163, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 9, 'Ekstrakurikuler dibuat: Paduan Suara', NULL, NULL, NULL, '2026-04-30 14:09:42'),
(164, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 10, 'Ekstrakurikuler dibuat: Seni Tari', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(165, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 5, 'FAQ dibuat: Di mana alamat SMP Negeri 1 Kotabaru?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(166, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 6, 'FAQ dibuat: Bagaimana cara menghubungi sekolah?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(167, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 7, 'FAQ dibuat: Apakah informasi PPDB tersedia di website?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(168, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 8, 'FAQ dibuat: Dokumen apa saja yang biasanya diperlukan untuk PPDB?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(169, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 9, 'FAQ dibuat: Apakah sekolah memiliki kegiatan ekstrakurikuler?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(170, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 10, 'FAQ dibuat: Bagaimana cara melihat agenda sekolah?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(171, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 11, 'FAQ dibuat: Apakah dokumen sekolah bisa diunduh?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(172, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 12, 'FAQ dibuat: Apa fokus pengembangan pembelajaran sekolah?', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(173, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 4, 'Konten PPDB dibuat: Langkah 1', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(174, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 5, 'Konten PPDB dibuat: Langkah 2', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(175, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 6, 'Konten PPDB dibuat: Langkah 3', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(176, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 7, 'Konten PPDB dibuat: Langkah 4', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(177, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 8, 'Konten PPDB dibuat: Jalur Pendaftaran', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(178, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 9, 'Konten PPDB dibuat: Jadwal Pelaksanaan', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(179, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 10, 'Konten PPDB dibuat: Layanan Panitia', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(180, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 11, 'Konten PPDB dibuat: Persyaratan 1', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(181, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 12, 'Konten PPDB dibuat: Persyaratan 2', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(182, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 13, 'Konten PPDB dibuat: Persyaratan 3', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(183, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 14, 'Konten PPDB dibuat: Persyaratan 4', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(184, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 15, 'Konten PPDB dibuat: Persyaratan 5', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(185, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 16, 'Konten PPDB dibuat: Catatan 1', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(186, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 17, 'Konten PPDB dibuat: Catatan 2', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(187, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 18, 'Konten PPDB dibuat: Catatan 3', NULL, NULL, NULL, '2026-04-30 14:09:43'),
(188, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, NULL, NULL, '2026-04-30 14:09:55'),
(189, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 8, 'Akun admin dibuat: smoke-20260430161018-editor', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(190, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 8, 'Akun admin dihapus: smoke-20260430161018-editor', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(191, 1, 'admin', 'superadmin', 'create', 'news', 'news', 14, 'Berita dibuat: SMOKE-20260430161018-Berita', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(192, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 14, 'Berita dihapus: SMOKE-20260430161018-Berita', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(193, 1, 'admin', 'superadmin', 'create', 'news', 'news', 15, 'Berita dibuat: SMOKE-20260430161018-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(194, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 8, 'Galeri dibuat: SMOKE-20260430161018-Galeri', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(195, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 8, 'Galeri dihapus: SMOKE-20260430161018-Galeri', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(196, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 8, 'Pengumuman dibuat: SMOKE-20260430161018-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(197, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 8, 'Pengumuman dihapus: SMOKE-20260430161018-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(198, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 71, 'Data guru/staf dibuat: SMOKE-20260430161018-Guru', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(199, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 71, 'Data guru/staf dihapus: SMOKE-20260430161018-Guru', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(200, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 9, 'Agenda dibuat: SMOKE-20260430161018-Agenda', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(201, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 9, 'Agenda dihapus: SMOKE-20260430161018-Agenda', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(202, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 5, 'Dokumen dibuat: SMOKE-20260430161018-Dokumen', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(203, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 5, 'Dokumen dihapus: SMOKE-20260430161018-Dokumen', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(204, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 7, 'Prestasi dibuat: SMOKE-20260430161018-Prestasi', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(205, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 7, 'Prestasi dihapus: SMOKE-20260430161018-Prestasi', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(206, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 11, 'Ekstrakurikuler dibuat: SMOKE-20260430161018-Ekskul', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(207, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 11, 'Ekstrakurikuler dihapus: SMOKE-20260430161018-Ekskul', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(208, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 21, 'Fasilitas dibuat: SMOKE-20260430161018-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(209, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 21, 'Fasilitas dihapus: SMOKE-20260430161018-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(210, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 13, 'FAQ dibuat: SMOKE-20260430161018-FAQ?', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(211, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 13, 'FAQ dihapus: SMOKE-20260430161018-FAQ?', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(212, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 19, 'Konten PPDB dibuat: SMOKE-20260430161018-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(213, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 19, 'Konten PPDB dihapus: SMOKE-20260430161018-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(214, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 5, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(215, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 5, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(216, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 7, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:10:18'),
(217, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 7, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:10:18'),
(218, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 9, 'Akun admin dibuat: smoke-20260430161303-editor', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(219, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 9, 'Akun admin dihapus: smoke-20260430161303-editor', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(220, 1, 'admin', 'superadmin', 'create', 'news', 'news', 16, 'Berita dibuat: SMOKE-20260430161303-Berita', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(221, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 16, 'Berita dihapus: SMOKE-20260430161303-Berita', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(222, 1, 'admin', 'superadmin', 'create', 'news', 'news', 17, 'Berita dibuat: SMOKE-20260430161303-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(223, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 9, 'Galeri dibuat: SMOKE-20260430161303-Galeri', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(224, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 9, 'Galeri dihapus: SMOKE-20260430161303-Galeri', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(225, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 9, 'Pengumuman dibuat: SMOKE-20260430161303-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(226, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 9, 'Pengumuman dihapus: SMOKE-20260430161303-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(227, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 72, 'Data guru/staf dibuat: SMOKE-20260430161303-Guru', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(228, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 72, 'Data guru/staf dihapus: SMOKE-20260430161303-Guru', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(229, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 10, 'Agenda dibuat: SMOKE-20260430161303-Agenda', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(230, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 10, 'Agenda dihapus: SMOKE-20260430161303-Agenda', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(231, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 6, 'Dokumen dibuat: SMOKE-20260430161303-Dokumen', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(232, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 6, 'Dokumen dihapus: SMOKE-20260430161303-Dokumen', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(233, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 8, 'Prestasi dibuat: SMOKE-20260430161303-Prestasi', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(234, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 8, 'Prestasi dihapus: SMOKE-20260430161303-Prestasi', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(235, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 12, 'Ekstrakurikuler dibuat: SMOKE-20260430161303-Ekskul', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(236, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 12, 'Ekstrakurikuler dihapus: SMOKE-20260430161303-Ekskul', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(237, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 22, 'Fasilitas dibuat: SMOKE-20260430161303-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(238, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 22, 'Fasilitas dihapus: SMOKE-20260430161303-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(239, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 14, 'FAQ dibuat: SMOKE-20260430161303-FAQ?', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(240, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 14, 'FAQ dihapus: SMOKE-20260430161303-FAQ?', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(241, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 20, 'Konten PPDB dibuat: SMOKE-20260430161303-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(242, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 20, 'Konten PPDB dihapus: SMOKE-20260430161303-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(243, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 6, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(244, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 6, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(245, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 8, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:13:03'),
(246, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 8, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:13:03'),
(247, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 10, 'Akun admin dibuat: smoke-20260430161603-editor', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(248, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 10, 'Akun admin dihapus: smoke-20260430161603-editor', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(249, 1, 'admin', 'superadmin', 'create', 'news', 'news', 18, 'Berita dibuat: SMOKE-20260430161603-Berita', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(250, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 18, 'Berita dihapus: SMOKE-20260430161603-Berita', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(251, 1, 'admin', 'superadmin', 'create', 'news', 'news', 19, 'Berita dibuat: SMOKE-20260430161603-Berita Draft', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(252, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 10, 'Galeri dibuat: SMOKE-20260430161603-Galeri', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(253, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 10, 'Galeri dihapus: SMOKE-20260430161603-Galeri', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(254, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 10, 'Pengumuman dibuat: SMOKE-20260430161603-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(255, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 10, 'Pengumuman dihapus: SMOKE-20260430161603-Pengumuman', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(256, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 73, 'Data guru/staf dibuat: SMOKE-20260430161603-Guru', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(257, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 73, 'Data guru/staf dihapus: SMOKE-20260430161603-Guru', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(258, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 11, 'Agenda dibuat: SMOKE-20260430161603-Agenda', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(259, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 11, 'Agenda dihapus: SMOKE-20260430161603-Agenda', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(260, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 7, 'Dokumen dibuat: SMOKE-20260430161603-Dokumen', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(261, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 7, 'Dokumen dihapus: SMOKE-20260430161603-Dokumen', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(262, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 9, 'Prestasi dibuat: SMOKE-20260430161603-Prestasi', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(263, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 9, 'Prestasi dihapus: SMOKE-20260430161603-Prestasi', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(264, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 13, 'Ekstrakurikuler dibuat: SMOKE-20260430161603-Ekskul', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(265, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 13, 'Ekstrakurikuler dihapus: SMOKE-20260430161603-Ekskul', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(266, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 23, 'Fasilitas dibuat: SMOKE-20260430161603-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(267, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 23, 'Fasilitas dihapus: SMOKE-20260430161603-Fasilitas', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(268, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 15, 'FAQ dibuat: SMOKE-20260430161603-FAQ?', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(269, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 15, 'FAQ dihapus: SMOKE-20260430161603-FAQ?', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(270, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 21, 'Konten PPDB dibuat: SMOKE-20260430161603-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(271, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 21, 'Konten PPDB dihapus: SMOKE-20260430161603-Konten PPDB', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(272, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 7, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(273, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 7, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(274, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 9, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260430-0001\"}', NULL, NULL, '2026-04-30 14:16:03'),
(275, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 9, 'Pendaftar PPDB dihapus: PPDB-20260430-0001', NULL, NULL, NULL, '2026-04-30 14:16:03'),
(276, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 7, 'Data guru/staf dihapus: S a r n o, S.Pd.Ind', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-30 14:21:01'),
(277, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 11, 'Akun admin dibuat: smoke-20260501003222-editor', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(278, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 11, 'Akun admin dihapus: smoke-20260501003222-editor', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(279, 1, 'admin', 'superadmin', 'create', 'news', 'news', 20, 'Berita dibuat: SMOKE-20260501003222-Berita', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(280, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 20, 'Berita dihapus: SMOKE-20260501003222-Berita', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(281, 1, 'admin', 'superadmin', 'create', 'news', 'news', 21, 'Berita dibuat: SMOKE-20260501003222-Berita Draft', NULL, NULL, NULL, '2026-04-30 22:32:22');
INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `username`, `role`, `action`, `module`, `entity_type`, `entity_id`, `description`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
(282, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 11, 'Galeri dibuat: SMOKE-20260501003222-Galeri', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(283, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 11, 'Galeri dihapus: SMOKE-20260501003222-Galeri', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(284, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 11, 'Pengumuman dibuat: SMOKE-20260501003222-Pengumuman', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(285, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 11, 'Pengumuman dihapus: SMOKE-20260501003222-Pengumuman', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(286, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 75, 'Data guru/staf dibuat: SMOKE-20260501003222-Guru', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(287, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 75, 'Data guru/staf dihapus: SMOKE-20260501003222-Guru', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(288, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 12, 'Agenda dibuat: SMOKE-20260501003222-Agenda', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(289, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 12, 'Agenda dihapus: SMOKE-20260501003222-Agenda', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(290, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 8, 'Dokumen dibuat: SMOKE-20260501003222-Dokumen', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(291, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 8, 'Dokumen dihapus: SMOKE-20260501003222-Dokumen', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(292, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 10, 'Prestasi dibuat: SMOKE-20260501003222-Prestasi', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(293, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 10, 'Prestasi dihapus: SMOKE-20260501003222-Prestasi', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(294, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 14, 'Ekstrakurikuler dibuat: SMOKE-20260501003222-Ekskul', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(295, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 14, 'Ekstrakurikuler dihapus: SMOKE-20260501003222-Ekskul', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(296, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 24, 'Fasilitas dibuat: SMOKE-20260501003222-Fasilitas', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(297, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 24, 'Fasilitas dihapus: SMOKE-20260501003222-Fasilitas', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(298, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 16, 'FAQ dibuat: SMOKE-20260501003222-FAQ?', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(299, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 16, 'FAQ dihapus: SMOKE-20260501003222-FAQ?', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(300, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 22, 'Konten PPDB dibuat: SMOKE-20260501003222-Konten PPDB', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(301, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 22, 'Konten PPDB dihapus: SMOKE-20260501003222-Konten PPDB', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(302, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 8, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(303, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 8, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(304, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 10, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 22:32:22'),
(305, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 10, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 22:32:22'),
(306, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 12, 'Akun admin dibuat: smoke-20260501003431-editor', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(307, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 12, 'Akun admin dihapus: smoke-20260501003431-editor', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(308, 1, 'admin', 'superadmin', 'create', 'news', 'news', 22, 'Berita dibuat: SMOKE-20260501003431-Berita', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(309, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 22, 'Berita dihapus: SMOKE-20260501003431-Berita', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(310, 1, 'admin', 'superadmin', 'create', 'news', 'news', 23, 'Berita dibuat: SMOKE-20260501003431-Berita Draft', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(311, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 12, 'Galeri dibuat: SMOKE-20260501003431-Galeri', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(312, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 12, 'Galeri dihapus: SMOKE-20260501003431-Galeri', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(313, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 12, 'Pengumuman dibuat: SMOKE-20260501003431-Pengumuman', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(314, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 12, 'Pengumuman dihapus: SMOKE-20260501003431-Pengumuman', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(315, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 76, 'Data guru/staf dibuat: SMOKE-20260501003431-Guru', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(316, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 76, 'Data guru/staf dihapus: SMOKE-20260501003431-Guru', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(317, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 13, 'Agenda dibuat: SMOKE-20260501003431-Agenda', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(318, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 13, 'Agenda dihapus: SMOKE-20260501003431-Agenda', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(319, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 9, 'Dokumen dibuat: SMOKE-20260501003431-Dokumen', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(320, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 9, 'Dokumen dihapus: SMOKE-20260501003431-Dokumen', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(321, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 11, 'Prestasi dibuat: SMOKE-20260501003431-Prestasi', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(322, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 11, 'Prestasi dihapus: SMOKE-20260501003431-Prestasi', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(323, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 15, 'Ekstrakurikuler dibuat: SMOKE-20260501003431-Ekskul', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(324, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 15, 'Ekstrakurikuler dihapus: SMOKE-20260501003431-Ekskul', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(325, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 25, 'Fasilitas dibuat: SMOKE-20260501003431-Fasilitas', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(326, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 25, 'Fasilitas dihapus: SMOKE-20260501003431-Fasilitas', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(327, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 17, 'FAQ dibuat: SMOKE-20260501003431-FAQ?', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(328, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 17, 'FAQ dihapus: SMOKE-20260501003431-FAQ?', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(329, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 23, 'Konten PPDB dibuat: SMOKE-20260501003431-Konten PPDB', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(330, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 23, 'Konten PPDB dihapus: SMOKE-20260501003431-Konten PPDB', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(331, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 9, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(332, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 9, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(333, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 11, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 22:34:31'),
(334, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 11, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 22:34:31'),
(335, NULL, 'admin', NULL, 'login_failed', 'auth', NULL, NULL, 'Percobaan login admin gagal.', '{\"username\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 22:58:15'),
(336, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 22:58:33'),
(337, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 13, 'Akun admin dibuat: smoke-20260501011810-editor', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(338, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 13, 'Akun admin dihapus: smoke-20260501011810-editor', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(339, 1, 'admin', 'superadmin', 'create', 'news', 'news', 24, 'Berita dibuat: SMOKE-20260501011810-Berita', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(340, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 24, 'Berita dihapus: SMOKE-20260501011810-Berita', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(341, 1, 'admin', 'superadmin', 'create', 'news', 'news', 25, 'Berita dibuat: SMOKE-20260501011810-Berita Draft', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(342, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 13, 'Galeri dibuat: SMOKE-20260501011810-Galeri', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(343, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 13, 'Galeri dihapus: SMOKE-20260501011810-Galeri', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(344, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 13, 'Pengumuman dibuat: SMOKE-20260501011810-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(345, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 13, 'Pengumuman dihapus: SMOKE-20260501011810-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(346, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 77, 'Data guru/staf dibuat: SMOKE-20260501011810-Guru', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(347, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 77, 'Data guru/staf dihapus: SMOKE-20260501011810-Guru', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(348, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 14, 'Agenda dibuat: SMOKE-20260501011810-Agenda', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(349, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 14, 'Agenda dihapus: SMOKE-20260501011810-Agenda', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(350, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 10, 'Dokumen dibuat: SMOKE-20260501011810-Dokumen', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(351, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 10, 'Dokumen dihapus: SMOKE-20260501011810-Dokumen', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(352, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 12, 'Prestasi dibuat: SMOKE-20260501011810-Prestasi', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(353, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 12, 'Prestasi dihapus: SMOKE-20260501011810-Prestasi', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(354, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 16, 'Ekstrakurikuler dibuat: SMOKE-20260501011810-Ekskul', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(355, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 16, 'Ekstrakurikuler dihapus: SMOKE-20260501011810-Ekskul', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(356, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 26, 'Fasilitas dibuat: SMOKE-20260501011810-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(357, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 26, 'Fasilitas dihapus: SMOKE-20260501011810-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(358, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 18, 'FAQ dibuat: SMOKE-20260501011810-FAQ?', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(359, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 18, 'FAQ dihapus: SMOKE-20260501011810-FAQ?', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(360, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 24, 'Konten PPDB dibuat: SMOKE-20260501011810-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(361, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 24, 'Konten PPDB dihapus: SMOKE-20260501011810-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(362, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 10, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(363, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 10, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(364, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 12, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 23:18:10'),
(365, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 12, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 23:18:10'),
(366, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 14, 'Akun admin dibuat: smoke-20260501012257-editor', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(367, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 14, 'Akun admin dihapus: smoke-20260501012257-editor', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(368, 1, 'admin', 'superadmin', 'create', 'news', 'news', 26, 'Berita dibuat: SMOKE-20260501012257-Berita', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(369, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 26, 'Berita dihapus: SMOKE-20260501012257-Berita', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(370, 1, 'admin', 'superadmin', 'create', 'news', 'news', 27, 'Berita dibuat: SMOKE-20260501012257-Berita Draft', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(371, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 14, 'Galeri dibuat: SMOKE-20260501012257-Galeri', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(372, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 14, 'Galeri dihapus: SMOKE-20260501012257-Galeri', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(373, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 14, 'Pengumuman dibuat: SMOKE-20260501012257-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(374, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 14, 'Pengumuman dihapus: SMOKE-20260501012257-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(375, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 78, 'Data guru/staf dibuat: SMOKE-20260501012257-Guru', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(376, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 78, 'Data guru/staf dihapus: SMOKE-20260501012257-Guru', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(377, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 15, 'Agenda dibuat: SMOKE-20260501012257-Agenda', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(378, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 15, 'Agenda dihapus: SMOKE-20260501012257-Agenda', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(379, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 11, 'Dokumen dibuat: SMOKE-20260501012257-Dokumen', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(380, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 11, 'Dokumen dihapus: SMOKE-20260501012257-Dokumen', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(381, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 13, 'Prestasi dibuat: SMOKE-20260501012257-Prestasi', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(382, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 13, 'Prestasi dihapus: SMOKE-20260501012257-Prestasi', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(383, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 17, 'Ekstrakurikuler dibuat: SMOKE-20260501012257-Ekskul', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(384, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 17, 'Ekstrakurikuler dihapus: SMOKE-20260501012257-Ekskul', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(385, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 27, 'Fasilitas dibuat: SMOKE-20260501012257-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(386, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 27, 'Fasilitas dihapus: SMOKE-20260501012257-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(387, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 19, 'FAQ dibuat: SMOKE-20260501012257-FAQ?', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(388, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 19, 'FAQ dihapus: SMOKE-20260501012257-FAQ?', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(389, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 25, 'Konten PPDB dibuat: SMOKE-20260501012257-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(390, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 25, 'Konten PPDB dihapus: SMOKE-20260501012257-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(391, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 11, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(392, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 11, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(393, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 13, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 23:22:57'),
(394, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 13, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 23:22:57'),
(395, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 15, 'Akun admin dibuat: smoke-20260501012757-editor', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(396, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 15, 'Akun admin dihapus: smoke-20260501012757-editor', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(397, 1, 'admin', 'superadmin', 'create', 'news', 'news', 28, 'Berita dibuat: SMOKE-20260501012757-Berita', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(398, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 28, 'Berita dihapus: SMOKE-20260501012757-Berita', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(399, 1, 'admin', 'superadmin', 'create', 'news', 'news', 29, 'Berita dibuat: SMOKE-20260501012757-Berita Draft', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(400, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 15, 'Galeri dibuat: SMOKE-20260501012757-Galeri', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(401, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 15, 'Galeri dihapus: SMOKE-20260501012757-Galeri', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(402, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 15, 'Pengumuman dibuat: SMOKE-20260501012757-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(403, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 15, 'Pengumuman dihapus: SMOKE-20260501012757-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(404, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 79, 'Data guru/staf dibuat: SMOKE-20260501012757-Guru', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(405, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 79, 'Data guru/staf dihapus: SMOKE-20260501012757-Guru', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(406, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 16, 'Agenda dibuat: SMOKE-20260501012757-Agenda', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(407, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 16, 'Agenda dihapus: SMOKE-20260501012757-Agenda', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(408, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 12, 'Dokumen dibuat: SMOKE-20260501012757-Dokumen', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(409, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 12, 'Dokumen dihapus: SMOKE-20260501012757-Dokumen', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(410, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 14, 'Prestasi dibuat: SMOKE-20260501012757-Prestasi', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(411, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 14, 'Prestasi dihapus: SMOKE-20260501012757-Prestasi', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(412, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 18, 'Ekstrakurikuler dibuat: SMOKE-20260501012757-Ekskul', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(413, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 18, 'Ekstrakurikuler dihapus: SMOKE-20260501012757-Ekskul', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(414, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 28, 'Fasilitas dibuat: SMOKE-20260501012757-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(415, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 28, 'Fasilitas dihapus: SMOKE-20260501012757-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(416, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 20, 'FAQ dibuat: SMOKE-20260501012757-FAQ?', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(417, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 20, 'FAQ dihapus: SMOKE-20260501012757-FAQ?', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(418, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 26, 'Konten PPDB dibuat: SMOKE-20260501012757-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(419, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 26, 'Konten PPDB dihapus: SMOKE-20260501012757-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(420, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 12, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(421, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 12, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(422, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 14, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 23:27:57'),
(423, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 14, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 23:27:57'),
(424, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 16, 'Akun admin dibuat: smoke-20260501013657-editor', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(425, 1, 'admin', 'superadmin', 'delete', 'admins', 'admin', 16, 'Akun admin dihapus: smoke-20260501013657-editor', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(426, 1, 'admin', 'superadmin', 'create', 'news', 'news', 30, 'Berita dibuat: SMOKE-20260501013657-Berita', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(427, 1, 'admin', 'superadmin', 'delete', 'news', 'news', 30, 'Berita dihapus: SMOKE-20260501013657-Berita', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(428, 1, 'admin', 'superadmin', 'create', 'news', 'news', 31, 'Berita dibuat: SMOKE-20260501013657-Berita Draft', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(429, 1, 'admin', 'superadmin', 'create', 'gallery', 'gallery_item', 16, 'Galeri dibuat: SMOKE-20260501013657-Galeri', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(430, 1, 'admin', 'superadmin', 'delete', 'gallery', 'gallery_item', 16, 'Galeri dihapus: SMOKE-20260501013657-Galeri', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(431, 1, 'admin', 'superadmin', 'create', 'announcements', 'announcement', 16, 'Pengumuman dibuat: SMOKE-20260501013657-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(432, 1, 'admin', 'superadmin', 'delete', 'announcements', 'announcement', 16, 'Pengumuman dihapus: SMOKE-20260501013657-Pengumuman', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(433, 1, 'admin', 'superadmin', 'create', 'staff', 'staff_member', 80, 'Data guru/staf dibuat: SMOKE-20260501013657-Guru', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(434, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 80, 'Data guru/staf dihapus: SMOKE-20260501013657-Guru', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(435, 1, 'admin', 'superadmin', 'create', 'agenda', 'agenda_item', 17, 'Agenda dibuat: SMOKE-20260501013657-Agenda', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(436, 1, 'admin', 'superadmin', 'delete', 'agenda', 'agenda_item', 17, 'Agenda dihapus: SMOKE-20260501013657-Agenda', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(437, 1, 'admin', 'superadmin', 'create', 'documents', 'school_document', 13, 'Dokumen dibuat: SMOKE-20260501013657-Dokumen', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(438, 1, 'admin', 'superadmin', 'delete', 'documents', 'school_document', 13, 'Dokumen dihapus: SMOKE-20260501013657-Dokumen', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(439, 1, 'admin', 'superadmin', 'create', 'achievements', 'achievement', 15, 'Prestasi dibuat: SMOKE-20260501013657-Prestasi', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(440, 1, 'admin', 'superadmin', 'delete', 'achievements', 'achievement', 15, 'Prestasi dihapus: SMOKE-20260501013657-Prestasi', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(441, 1, 'admin', 'superadmin', 'create', 'extracurriculars', 'extracurricular', 19, 'Ekstrakurikuler dibuat: SMOKE-20260501013657-Ekskul', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(442, 1, 'admin', 'superadmin', 'delete', 'extracurriculars', 'extracurricular', 19, 'Ekstrakurikuler dihapus: SMOKE-20260501013657-Ekskul', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(443, 1, 'admin', 'superadmin', 'create', 'facilities', 'facility', 29, 'Fasilitas dibuat: SMOKE-20260501013657-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(444, 1, 'admin', 'superadmin', 'delete', 'facilities', 'facility', 29, 'Fasilitas dihapus: SMOKE-20260501013657-Fasilitas', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(445, 1, 'admin', 'superadmin', 'create', 'faqs', 'faq', 21, 'FAQ dibuat: SMOKE-20260501013657-FAQ?', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(446, 1, 'admin', 'superadmin', 'delete', 'faqs', 'faq', 21, 'FAQ dihapus: SMOKE-20260501013657-FAQ?', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(447, 1, 'admin', 'superadmin', 'create', 'ppdb_content', 'ppdb_entry', 27, 'Konten PPDB dibuat: SMOKE-20260501013657-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(448, 1, 'admin', 'superadmin', 'delete', 'ppdb_content', 'ppdb_entry', 27, 'Konten PPDB dihapus: SMOKE-20260501013657-Konten PPDB', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(449, 1, 'admin', 'superadmin', 'update_status', 'contact_messages', 'contact_message', 13, 'Status pesan masuk diubah menjadi selesai.', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(450, 1, 'admin', 'superadmin', 'delete', 'contact_messages', 'contact_message', 13, 'Pesan masuk dihapus.', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(451, 1, 'admin', 'superadmin', 'update_status', 'ppdb_applications', 'ppdb_application', 15, 'Status pendaftar PPDB diubah menjadi diverifikasi.', '{\"registration_number\":\"PPDB-20260501-0001\"}', NULL, NULL, '2026-04-30 23:36:57'),
(452, 1, 'admin', 'superadmin', 'delete', 'ppdb_applications', 'ppdb_application', 15, 'Pendaftar PPDB dihapus: PPDB-20260501-0001', NULL, NULL, NULL, '2026-04-30 23:36:57'),
(453, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 00:34:40'),
(454, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 17, 'Akun admin dibuat: hendri', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 01:36:22'),
(455, 1, 'admin', 'superadmin', 'logout', 'auth', NULL, NULL, 'Logout admin.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 01:36:27'),
(456, 17, 'hendri', 'editor', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 01:36:36'),
(457, 17, 'hendri', 'editor', 'create', 'gallery', 'gallery_item', 17, 'Galeri dibuat: test', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 02:20:51'),
(458, 17, 'hendri', 'editor', 'logout', 'auth', NULL, NULL, 'Logout admin.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:01:00'),
(459, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:01:05'),
(460, 1, 'admin', 'superadmin', 'create', 'admins', 'admin', 19, 'Akun admin dibuat: Yulianoor', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:10:43'),
(461, 1, 'admin', 'superadmin', 'logout', 'auth', NULL, NULL, 'Logout admin.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:10:50'),
(462, 19, 'Yulianoor', 'editor', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:11:00'),
(463, 19, 'Yulianoor', 'editor', 'logout', 'auth', NULL, NULL, 'Logout admin.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:11:40'),
(464, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:11:49'),
(465, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:18:05'),
(466, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 04:18:48'),
(467, 1, 'admin', 'superadmin', 'update', 'achievements', 'achievement', 6, 'Prestasi diperbarui: KABAR PRESTASI: Kilau Bakat di Panggung SMADAFest!', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 07:53:50'),
(468, 1, 'admin', 'superadmin', 'update', 'achievements', 'achievement', 6, 'Prestasi diperbarui: KABAR PRESTASI: Kilau Bakat di Panggung SMADAFest!', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 07:55:17'),
(469, 1, 'admin', 'superadmin', 'update', 'achievements', 'achievement', 6, 'Prestasi diperbarui: KABAR PRESTASI: Kilau Bakat di Panggung SMADAFest!', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-01 07:55:48'),
(470, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 21, 'Data guru/staf dihapus: Muhsin, S.Pd', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 15:22:38'),
(471, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 74, 'Data guru/staf dihapus: S a r n o, S.Pd.Ind', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 15:22:45'),
(472, 1, 'admin', 'superadmin', 'delete', 'staff', 'staff_member', 26, 'Data guru/staf dihapus: Roslinda Anita, S.Pd', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-02 15:22:54'),
(473, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 11:12:00'),
(474, 1, 'admin', 'superadmin', 'update', 'news', 'news', 12, 'Berita diperbarui: Ekstrakurikuler Jurnalistik Aktif Berkarya', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 11:22:46'),
(475, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 07:17:59'),
(476, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 07:18:59'),
(477, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 08:13:18'),
(478, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:36:01'),
(479, 1, 'admin', 'superadmin', 'logout', 'auth', NULL, NULL, 'Logout admin.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:41:10'),
(480, 1, 'admin', 'superadmin', 'login', 'auth', NULL, NULL, 'Login admin berhasil.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:41:12'),
(481, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:41:47'),
(482, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:46:52'),
(483, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 10:59:13'),
(484, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:44:42'),
(485, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:45:17'),
(486, 1, 'admin', 'superadmin', 'update', 'settings', 'site_settings', NULL, 'Pengaturan website diperbarui.', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:46:40');

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
-- Indeks untuk tabel `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_activity_created_at` (`created_at`),
  ADD KEY `idx_admin_activity_action` (`action`),
  ADD KEY `idx_admin_activity_module` (`module`),
  ADD KEY `idx_admin_activity_username` (`username`);

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
-- AUTO_INCREMENT untuk tabel `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=487;

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
