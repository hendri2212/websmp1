<?php

// Only authentication and empty counter dependencies; not a complete CRUD schema.
return [
    'admins' => "CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(120) NULL, role ENUM('superadmin','editor','ppdb') NOT NULL DEFAULT 'editor',
        is_active TINYINT(1) NOT NULL DEFAULT 1, password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'site_settings' => "CREATE TABLE site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value LONGTEXT NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    'admin_activity_logs' => "CREATE TABLE admin_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, username VARCHAR(80) NULL,
        role VARCHAR(40) NULL, action VARCHAR(80) NOT NULL, module VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NULL, entity_id INT NULL, description TEXT NOT NULL,
        metadata LONGTEXT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_activity_created_at (created_at), INDEX idx_admin_activity_action (action),
        INDEX idx_admin_activity_module (module), INDEX idx_admin_activity_username (username)
    ) ENGINE=InnoDB",
    'agenda_items' => "CREATE TABLE agenda_items (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026')",
    'school_documents' => "CREATE TABLE school_documents (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026')",
    'school_achievements' => "CREATE TABLE school_achievements (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026')",
    'ppdb_applications' => "CREATE TABLE ppdb_applications (
        id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
        status ENUM('baru','diverifikasi','diterima','ditolak') NOT NULL DEFAULT 'baru',
        family_card_path VARCHAR(255) NULL, birth_certificate_path VARCHAR(255) NULL,
        report_card_path VARCHAR(255) NULL, photo_path VARCHAR(255) NULL
    )",
    'contact_messages' => "CREATE TABLE contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY, status ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru'
    )",
];
