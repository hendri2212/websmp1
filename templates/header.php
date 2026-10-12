<?php
$content = $content ?? site_content();
$schoolProfile = $content['schoolProfile'];
$logoPath = $schoolProfile['logoPath'] ?? '/public/logo-sekolah.png';
$pageTitle = $pageTitle ?? $schoolProfile['name'];
$metaDescription = plain_text_excerpt($metaDescription ?? ($schoolProfile['description'] ?? ''), 160);
$canonicalUrl = absolute_url(current_path() === '/' ? '' : ltrim(current_path(), '/'));
$metaImageUrl = absolute_url(ltrim($metaImage ?? $logoPath, '/'));
$stylesheetPath = __DIR__ . '/../public/assets/css/style.css';
$stylesheetVersion = is_file($stylesheetPath) ? (string) filemtime($stylesheetPath) : (string) time();
$faviconPath = __DIR__ . '/../public/favicon.png';
$faviconVersion = is_file($faviconPath) ? (string) filemtime($faviconPath) : $stylesheetVersion;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="<?= e($metaDescription); ?>">
    <link rel="canonical" href="<?= e($canonicalUrl); ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="<?= e($pageTitle); ?>">
    <meta property="og:description" content="<?= e($metaDescription); ?>">
    <meta property="og:url" content="<?= e($canonicalUrl); ?>">
    <meta property="og:image" content="<?= e($metaImageUrl); ?>">
    <meta property="og:site_name" content="<?= e($schoolProfile['name']); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle); ?>">
    <meta name="twitter:description" content="<?= e($metaDescription); ?>">
    <meta name="twitter:image" content="<?= e($metaImageUrl); ?>">
    <link rel="icon" type="image/png" href="<?= e(asset('public/favicon.png?v=' . $faviconVersion)); ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('public/favicon.png?v=' . $faviconVersion)); ?>">
    <link rel="stylesheet" href="<?= e(asset('public/assets/css/style.css?v=' . $stylesheetVersion)); ?>">
    <style><?= theme_css_variables($content['siteTheme'] ?? []); ?></style>
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="<?= e(base_url()); ?>">
                <img src="<?= e(asset(ltrim($logoPath, '/'))); ?>" alt="Logo sekolah">
                <div>
                    <strong><?= e($schoolProfile['name']); ?></strong>
                    <span>Website Resmi Sekolah</span>
                </div>
            </a>
            <form class="header-search" action="<?= e(base_url('cari')); ?>" method="get" role="search">
                <input type="search" name="q" value="<?= e($_GET['q'] ?? ''); ?>" placeholder="Cari..." aria-label="Cari informasi website">
                <button type="submit" aria-label="Cari">Cari</button>
            </form>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                <span class="menu-toggle-icon" aria-hidden="true"></span>
                <span>Menu</span>
            </button>
            <nav class="site-nav" id="site-nav">
                <?php foreach ($content['navigation'] as $item): ?>
                    <?php if (($item['visible'] ?? true) === true): ?>
                        <a class="<?= is_active_path($item['href']) ? 'active' : ''; ?>" href="<?= e(base_url(ltrim($item['href'], '/'))); ?>">
                            <?= e($item['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
    <main>
