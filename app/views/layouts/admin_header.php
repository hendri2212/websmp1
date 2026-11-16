<?php
$pageTitle = $pageTitle ?? 'Admin';
$showAdminSidebar = admin_is_logged_in();

$logoPath = $content['schoolProfile']['logoPath'] ?? '';
$stylesheetPath = APP_ROOT . '/public/assets/css/style.css';
$stylesheetVersion = is_file($stylesheetPath) ? (string) filemtime($stylesheetPath) : (string) time();
$faviconPath = APP_ROOT . '/public/favicon.png';
$faviconVersion = is_file($faviconPath) ? (string) filemtime($faviconPath) : $stylesheetVersion;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?= e(asset('public/favicon.png?v=' . $faviconVersion)); ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('public/favicon.png?v=' . $faviconVersion)); ?>">
    <link rel="stylesheet" href="<?= e(asset('public/assets/css/style.css?v=' . $stylesheetVersion)); ?>">
    <style><?= theme_css_variables($content['siteTheme'] ?? []); ?></style>
</head>
<body class="admin-body">
<div class="admin-shell<?= $showAdminSidebar ? '' : ' admin-shell-login'; ?>">
    <?php if ($showAdminSidebar): ?>
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= e(base_url('admin/dashboard.php')); ?>">
            <img src="<?= e(asset('public/favicon.png?v=' . $faviconVersion)); ?>" alt="Logo sekolah">
            <span>Admin Sekolah<small>Panel pengelolaan</small></span>
        </a>
        <?php
        $currentAdminPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $adminMenuGroups = [
            ['label' => 'Publikasi', 'icon' => 'M4 5h16v14H4z M8 9h8 M8 13h5', 'items' => [
                ['news', 'news', 'Berita'],
                ['announcements', 'announcements', 'Pengumuman'],
                ['agenda', 'agenda', 'Agenda'],
                ['gallery', 'gallery', 'Galeri'],
                ['documents', 'documents', 'Dokumen'],
            ]],
            ['label' => 'Profil Sekolah', 'icon' => 'M3 10l9-6 9 6 M5 10v10h14V10 M10 20v-6h4v6', 'items' => [
                ['staff', 'staff', 'Guru & Staf'],
                ['facilities', 'facilities', 'Fasilitas'],
            ]],
            ['label' => 'Kesiswaan', 'icon' => 'M3 8l9-4 9 4-9 4z M7 10v6c3 3 7 3 10 0v-6 M21 8v8', 'items' => [
                ['achievements', 'achievements', 'Prestasi'],
                ['extracurriculars', 'extracurriculars', 'Ekstrakurikuler'],
                ['ppdb_content', 'ppdb', 'Informasi PPDB'],
            ]],
            ['label' => 'Layanan Publik', 'icon' => 'M4 5h16v12H9l-5 4z M8 9h8 M8 13h5', 'items' => [
                ['contact_messages', 'contact_messages', 'Pesan Masuk'],
                ['faqs', 'faqs', 'FAQ'],
            ]],
            ['label' => 'Sistem & Akun', 'icon' => 'M12 3v3 M12 18v3 M3 12h3 M18 12h3 M6 6l2 2 M16 16l2 2 M6 18l2-2 M16 8l2-2 M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0', 'items' => [
                ['settings', 'settings', 'Profil & Pengaturan'],
                ['admins', 'admins', 'Akun Admin'],
                ['db_backup', 'db_backup', 'Cadangan Data'],
                ['change_password', 'change_password', 'Ganti Password'],
            ]],
        ];
        $dashboardActive = $currentAdminPath === parse_url(base_url('admin/dashboard.php'), PHP_URL_PATH);
        ?>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <?php if (admin_can('dashboard')): ?>
                <a class="admin-nav-dashboard<?= $dashboardActive ? ' is-active' : ''; ?>" href="<?= e(base_url('admin/dashboard.php')); ?>"<?= $dashboardActive ? ' aria-current="page"' : ''; ?>>
                    <svg class="admin-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z"/></svg>
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>
            <p class="admin-nav-caption">MENU PENGELOLAAN</p>
            <?php foreach ($adminMenuGroups as $group): ?>
                <?php
                $visibleItems = array_values(array_filter($group['items'], static fn (array $item): bool => admin_can($item[0])));
                if (!$visibleItems) { continue; }
                $groupActive = false;
                $groupBadge = 0;
                foreach ($visibleItems as $item) {
                    if ($currentAdminPath === parse_url(base_url('admin/' . $item[1] . '.php'), PHP_URL_PATH)) {
                        $groupActive = true;
                    }
                    if ($item[0] === 'contact_messages') {
                        $groupBadge = (int) ($adminNotificationCounts['contact_messages'] ?? 0);
                    }
                }
                ?>
                <details class="admin-nav-group<?= $groupActive ? ' has-active' : ''; ?>"<?= $groupActive ? ' open' : ''; ?>>
                    <summary>
                        <svg class="admin-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($group['icon']); ?>"/></svg>
                        <span><?= e($group['label']); ?></span>
                        <?php if ($groupBadge > 0): ?><strong class="admin-nav-badge"><?= $groupBadge; ?></strong><?php endif; ?>
                    </summary>
                    <div class="admin-nav-submenu">
                        <?php foreach ($visibleItems as $item): ?>
                            <?php
                            $itemActive = $currentAdminPath === parse_url(base_url('admin/' . $item[1] . '.php'), PHP_URL_PATH);
                            $itemBadge = $item[0] === 'contact_messages' ? $groupBadge : 0;
                            ?>
                            <a<?= $itemActive ? ' class="is-active" aria-current="page"' : ''; ?> href="<?= e(base_url('admin/' . $item[1] . '.php')); ?>">
                                <span><?= e($item[2]); ?></span>
                                <?php if ($itemBadge > 0): ?><strong class="admin-nav-badge"><?= $itemBadge; ?></strong><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </nav>
        <form class="admin-nav-form" method="post" action="<?= e(base_url('admin/logout.php')); ?>">
            <?= csrf_field(); ?>
            <button type="submit">
                <svg class="admin-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H4v16h5 M9 12h12 M17 8l4 4-4 4"/></svg>
                <span>Logout</span>
            </button>
        </form>
    </aside>
    <?php endif; ?>
    <main class="admin-main">
