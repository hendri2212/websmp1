<?php

function admin_content_status_summary(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    return [
        ['permission' => 'news', 'label' => 'Berita', 'active' => news_count(true), 'total' => news_count(false), 'href' => 'admin/news.php'],
        ['permission' => 'achievements', 'label' => 'Prestasi', 'active' => achievement_count(true), 'total' => achievement_count(false), 'href' => 'admin/achievements.php'],
        ['permission' => 'agenda', 'label' => 'Agenda', 'active' => agenda_count(true), 'total' => agenda_count(false), 'href' => 'admin/agenda.php'],
        ['permission' => 'documents', 'label' => 'Dokumen', 'active' => document_count(true), 'total' => document_count(false), 'href' => 'admin/documents.php'],
        ['permission' => 'facilities', 'label' => 'Fasilitas', 'active' => facility_count(true), 'total' => facility_count(false), 'href' => 'admin/facilities.php'],
        ['permission' => 'extracurriculars', 'label' => 'Ekskul', 'active' => extracurricular_count(true), 'total' => extracurricular_count(false), 'href' => 'admin/extracurriculars.php'],
        ['permission' => 'staff', 'label' => 'Guru & Staf', 'active' => staff_count(true), 'total' => staff_count(false), 'href' => 'admin/staff.php'],
        ['permission' => 'faqs', 'label' => 'FAQ', 'active' => faq_count(true), 'total' => faq_count(false), 'href' => 'admin/faqs.php'],
    ];
}
