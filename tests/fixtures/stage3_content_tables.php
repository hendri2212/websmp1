<?php

declare(strict_types=1);

// Reviewed content-only column definitions; never execute schema.sql or its account seed.
$id = ['id' => 'INT AUTO_INCREMENT PRIMARY KEY'];
$created = ['created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'];
$publication = ['is_active' => 'TINYINT(1) NOT NULL DEFAULT 1'];
$order = ['sort_order' => 'INT NOT NULL DEFAULT 0'];
$year = ['academic_year' => "VARCHAR(20) NOT NULL DEFAULT '2025/2026'"];
$image = ['image_path' => "VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png'"];

return [
    'news' => $id + ['slug'=>'VARCHAR(180) NOT NULL UNIQUE', 'title'=>'VARCHAR(255) NOT NULL',
        'category'=>'VARCHAR(100) NOT NULL', 'excerpt'=>'TEXT NOT NULL', 'content'=>'LONGTEXT NOT NULL']
        + $image + ['published_at'=>'DATETIME NOT NULL'] + $publication + $created,
    'gallery_items' => $id + ['title'=>'VARCHAR(255) NOT NULL', 'description'=>'TEXT NOT NULL',
        'image_path'=>'VARCHAR(255) NOT NULL'] + $order + $created,
    'announcements' => $id + ['title'=>'VARCHAR(255) NOT NULL', 'content'=>'TEXT NOT NULL',
        'link_label'=>'VARCHAR(120) NULL', 'link_url'=>'VARCHAR(255) NULL', 'starts_at'=>'DATETIME NULL',
        'ends_at'=>'DATETIME NULL'] + $publication + $order + $created,
    'staff_members' => $id + ['name'=>'VARCHAR(160) NOT NULL', 'position'=>'VARCHAR(160) NOT NULL',
        'subject'=>'VARCHAR(160) NOT NULL', 'bio'=>'TEXT NOT NULL', 'phone'=>'VARCHAR(60) NULL',
        'email'=>'VARCHAR(180) NULL', 'photo_path'=>"VARCHAR(255) NOT NULL DEFAULT '/public/logo-sekolah.png'"]
        + $publication + $order + $created,
    'agenda_items' => $id + ['title'=>'VARCHAR(255) NOT NULL', 'category'=>'VARCHAR(120) NOT NULL',
        'location'=>'VARCHAR(180) NOT NULL', 'description'=>'TEXT NOT NULL', 'starts_at'=>'DATETIME NOT NULL',
        'ends_at'=>'DATETIME NULL'] + $publication + $year + $order + $created,
    'school_documents' => $id + ['title'=>'VARCHAR(255) NOT NULL', 'category'=>'VARCHAR(120) NOT NULL',
        'description'=>'TEXT NOT NULL', 'file_path'=>'VARCHAR(255) NOT NULL', 'original_filename'=>'VARCHAR(255) NOT NULL',
        'file_size'=>'INT NOT NULL DEFAULT 0', 'download_count'=>'INT NOT NULL DEFAULT 0']
        + $publication + $year + $order + $created,
    'school_achievements' => $id + ['title'=>'VARCHAR(255) NOT NULL', 'level'=>'VARCHAR(120) NOT NULL',
        'category'=>'VARCHAR(120) NOT NULL', 'achiever_name'=>'VARCHAR(180) NOT NULL', 'achievement_date'=>'DATE NOT NULL',
        'description'=>'TEXT NOT NULL'] + $image + $publication + $year + $order + $created,
    'extracurriculars' => $id + ['name'=>'VARCHAR(180) NOT NULL', 'category'=>'VARCHAR(120) NOT NULL',
        'coach'=>'VARCHAR(160) NULL', 'schedule_text'=>'VARCHAR(180) NULL', 'location'=>'VARCHAR(180) NULL',
        'description'=>'TEXT NOT NULL'] + $image + $publication + $order + $created,
    'school_facilities' => $id + ['name'=>'VARCHAR(180) NOT NULL', 'category'=>'VARCHAR(120) NOT NULL',
        'location'=>'VARCHAR(180) NULL', 'capacity'=>'VARCHAR(120) NULL', 'condition_status'=>"VARCHAR(120) NOT NULL DEFAULT 'Baik'",
        'description'=>'TEXT NOT NULL'] + $image + $publication + $order + $created,
    'faqs' => $id + ['question'=>'VARCHAR(255) NOT NULL', 'answer'=>'TEXT NOT NULL', 'category'=>'VARCHAR(120) NOT NULL']
        + $publication + $order + $created,
    'contact_messages' => $id + ['name'=>'VARCHAR(120) NOT NULL', 'email'=>'VARCHAR(180) NOT NULL',
        'phone'=>'VARCHAR(60) NOT NULL', 'subject'=>'VARCHAR(180) NOT NULL', 'message'=>'TEXT NOT NULL',
        'status'=>"ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru'"] + $created
        + ['updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
];
