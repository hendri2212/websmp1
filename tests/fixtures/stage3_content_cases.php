<?php

declare(strict_types=1);

$common = ['id'=>'', 'sort_order'=>'7', 'is_active'=>'1', 'academic_year'=>'2025/2026',
    'category'=>'Uji sintetis', 'description'=>'Konten sintetis bukan data sekolah',
    'existing_image_path'=>'/public/logo-sekolah.png'];

return [
    'news' => ['table'=>'news', 'field'=>'title', 'label'=>'BERITA', 'public'=>'/berita',
        'payload'=>$common + ['title'=>'', 'slug'=>'', 'excerpt'=>'Ringkasan sintetis', 'content'=>'Isi sintetis', 'published_at'=>'2026-10-06T09:00']],
    'gallery' => ['table'=>'gallery_items', 'field'=>'title', 'label'=>'GALERI', 'public'=>'/galeri', 'payload'=>$common + ['title'=>'']],
    'documents' => ['table'=>'school_documents', 'field'=>'title', 'label'=>'DOKUMEN', 'public'=>'/dokumen', 'payload'=>$common + ['title'=>'']],
    'announcements' => ['table'=>'announcements', 'field'=>'title', 'label'=>'PENGUMUMAN', 'public'=>'/',
        'payload'=>$common + ['title'=>'', 'content'=>'Pengumuman sintetis', 'link_label'=>'', 'link_url'=>'', 'starts_at'=>'', 'ends_at'=>'']],
    'staff' => ['table'=>'staff_members', 'field'=>'name', 'label'=>'STAF', 'public'=>'/guru-staf',
        'payload'=>$common + ['name'=>'', 'position'=>'Guru Uji', 'subject'=>'Uji', 'bio'=>'Bio sintetis', 'phone'=>'', 'email'=>'', 'existing_photo_path'=>'/public/logo-sekolah.png']],
    'agenda' => ['table'=>'agenda_items', 'field'=>'title', 'label'=>'AGENDA', 'public'=>'/agenda',
        'payload'=>$common + ['title'=>'', 'location'=>'Lokasi sintetis', 'starts_at'=>'2026-10-07T09:00', 'ends_at'=>'2026-10-07T10:00']],
    'achievements' => ['table'=>'school_achievements', 'field'=>'title', 'label'=>'PRESTASI', 'public'=>'/prestasi',
        'payload'=>$common + ['title'=>'', 'level'=>'Sekolah', 'achiever_name'=>'Peserta sintetis', 'achievement_date'=>'2026-10-06']],
    'facilities' => ['table'=>'school_facilities', 'field'=>'name', 'label'=>'FASILITAS', 'public'=>'/fasilitas',
        'payload'=>$common + ['name'=>'', 'location'=>'Lokasi sintetis', 'capacity'=>'10', 'condition_status'=>'Baik']],
    'extracurriculars' => ['table'=>'extracurriculars', 'field'=>'name', 'label'=>'EKSTRAKURIKULER', 'public'=>'/ekstrakurikuler',
        'payload'=>$common + ['name'=>'', 'coach'=>'Pembina sintetis', 'schedule_text'=>'Senin', 'location'=>'Lokasi sintetis']],
    'faqs' => ['table'=>'faqs', 'field'=>'question', 'label'=>'FAQ', 'public'=>'/faq', 'payload'=>$common + ['question'=>'', 'answer'=>'Jawaban sintetis']],
];
