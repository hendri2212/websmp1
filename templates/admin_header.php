<?php
$pageTitle = $pageTitle ?? 'Admin';
$content = $content ?? site_content();
$logoPath = $content['schoolProfile']['logoPath'] ?? '/public/logo-sekolah.png';
$stylesheetPath = __DIR__ . '/../public/assets/css/style.css';
$stylesheetVersion = is_file($stylesheetPath) ? (string) filemtime($stylesheetPath) : (string) time();
$faviconPath = __DIR__ . '/../public/favicon.png';
$faviconVersion = is_file($faviconPath) ? (string) filemtime($faviconPath) : $stylesheetVersion;
$adminNotificationCounts = [
    'ppdb_applications' => db_available() ? ppdb_application_count('baru') : 0,
    'contact_messages' => db_available() ? contact_message_count('baru') : 0,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?= asset('public/favicon.png?v=' . $faviconVersion); ?>">
    <link rel="apple-touch-icon" href="<?= asset('public/favicon.png?v=' . $faviconVersion); ?>">
    <link rel="stylesheet" href="<?= asset('public/assets/css/style.css?v=' . $stylesheetVersion); ?>">
    <style><?= theme_css_variables($content['siteTheme'] ?? []); ?></style>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= base_url('admin/dashboard.php'); ?>">
            <img src="<?= asset('public/favicon.png?v=' . $faviconVersion); ?>" alt="Logo sekolah">
            <span>Admin Sekolah</span>
        </a>
        <nav class="admin-nav">
            <?php
            $adminMenu = [
                ['permission' => 'dashboard', 'href' => 'admin/dashboard.php', 'label' => 'Dashboard'],
                ['permission' => 'settings', 'href' => 'admin/settings.php', 'label' => 'Profil & Pengaturan'],
                ['permission' => 'admins', 'href' => 'admin/admins.php', 'label' => 'Akun Admin'],
                ['permission' => 'db_backup', 'href' => 'admin/db_backup.php', 'label' => 'Backup Database'],
                ['permission' => 'activity_logs', 'href' => 'admin/activity_logs.php', 'label' => 'Log Aktivitas'],
                ['permission' => 'production_check', 'href' => 'admin/production_check.php', 'label' => 'Cek Production'],
                ['permission' => 'facilities', 'href' => 'admin/facilities.php', 'label' => 'Fasilitas'],
                ['permission' => 'agenda', 'href' => 'admin/agenda.php', 'label' => 'Agenda'],
                ['permission' => 'documents', 'href' => 'admin/documents.php', 'label' => 'Dokumen'],
                ['permission' => 'achievements', 'href' => 'admin/achievements.php', 'label' => 'Prestasi'],
                ['permission' => 'extracurriculars', 'href' => 'admin/extracurriculars.php', 'label' => 'Ekstrakurikuler'],
                ['permission' => 'staff', 'href' => 'admin/staff.php', 'label' => 'Guru & Staf'],
                ['permission' => 'news', 'href' => 'admin/news.php', 'label' => 'Berita'],
                ['permission' => 'announcements', 'href' => 'admin/announcements.php', 'label' => 'Pengumuman'],
                ['permission' => 'gallery', 'href' => 'admin/gallery.php', 'label' => 'Galeri'],
                ['permission' => 'ppdb_content', 'href' => 'admin/ppdb.php', 'label' => 'PPDB'],
                ['permission' => 'ppdb_applications', 'href' => 'admin/ppdb_applications.php', 'label' => 'Pendaftar PPDB', 'badge' => $adminNotificationCounts['ppdb_applications']],
                ['permission' => 'faqs', 'href' => 'admin/faqs.php', 'label' => 'FAQ'],
                ['permission' => 'contact_messages', 'href' => 'admin/contact_messages.php', 'label' => 'Pesan Masuk', 'badge' => $adminNotificationCounts['contact_messages']],
                ['permission' => 'change_password', 'href' => 'admin/change_password.php', 'label' => 'Ganti Password'],
            ];
            ?>
            <?php foreach ($adminMenu as $item): ?>
                <?php if (admin_can($item['permission'])): ?>
                    <a href="<?= base_url($item['href']); ?>">
                        <span><?= e($item['label']); ?></span>
                        <?php if (!empty($item['badge'])): ?>
                            <strong class="admin-nav-badge"><?= (int) $item['badge']; ?></strong>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <form class="admin-nav-form" method="post" action="<?= base_url('admin/logout.php'); ?>">
                <?= csrf_field(); ?>
                <button type="submit">Logout</button>
            </form>
        </nav>
    </aside>
    <main class="admin-main">
