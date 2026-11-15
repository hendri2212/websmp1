CREATE DATABASE IF NOT EXISTS website_sekolah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE website_sekolah;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(120) NULL,
    role ENUM('superadmin', 'editor', 'ppdb') NOT NULL DEFAULT 'editor',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    excerpt TEXT NOT NULL,
    content LONGTEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
    published_at DATETIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    link_label VARCHAR(120) NULL,
    link_url VARCHAR(255) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    position VARCHAR(160) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    bio TEXT NOT NULL,
    phone VARCHAR(60) NULL,
    email VARCHAR(180) NULL,
    photo_path VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agenda_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(120) NOT NULL,
    location VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS school_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    download_count INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS school_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    level VARCHAR(120) NOT NULL,
    category VARCHAR(120) NOT NULL,
    achiever_name VARCHAR(180) NOT NULL,
    achievement_date DATE NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS extracurriculars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(120) NOT NULL,
    coach VARCHAR(160) NULL,
    schedule_text VARCHAR(180) NULL,
    location VARCHAR(180) NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS school_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(120) NOT NULL,
    location VARCHAR(180) NULL,
    capacity VARCHAR(120) NULL,
    condition_status VARCHAR(120) NOT NULL DEFAULT 'Baik',
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ppdb_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ppdb_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(40) NOT NULL UNIQUE,
    student_name VARCHAR(160) NOT NULL,
    birth_place VARCHAR(120) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
    nisn VARCHAR(40) NOT NULL,
    previous_school VARCHAR(160) NOT NULL,
    parent_name VARCHAR(160) NOT NULL,
    parent_phone VARCHAR(60) NOT NULL,
    parent_email VARCHAR(180) NOT NULL,
    address TEXT NOT NULL,
    family_card_path VARCHAR(255) NOT NULL,
    birth_certificate_path VARCHAR(255) NOT NULL,
    report_card_path VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    consent_accepted_at DATETIME NULL,
    privacy_version VARCHAR(40) NULL,
    status ENUM('baru', 'diverifikasi', 'diterima', 'ditolak') NOT NULL DEFAULT 'baru',
    academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(60) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('baru', 'diproses', 'selesai') NOT NULL DEFAULT 'baru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- No default account. Provision the first administrator explicitly through the
-- offline CLI-only initialize_admin.php; never import this installer at runtime.
