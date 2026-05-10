<?php
$urls = [];
$addUrl = static function (string $path, string $priority = '0.7', string $changefreq = 'weekly') use (&$urls): void {
    $urls[] = [
        'loc' => absolute_url($path === '/' ? '' : ltrim($path, '/')),
        'priority' => $priority,
        'changefreq' => $changefreq,
    ];
};

foreach ($content['navigation'] as $item) {
    if (($item['visible'] ?? true) === true) {
        $addUrl($item['href'], $item['href'] === '/' ? '1.0' : '0.8');
    }
}

foreach (news_items() as $item) {
    $addUrl('/berita/' . $item['slug'], '0.6', 'monthly');
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= e($url['loc']); ?></loc>
        <changefreq><?= e($url['changefreq']); ?></changefreq>
        <priority><?= e($url['priority']); ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
