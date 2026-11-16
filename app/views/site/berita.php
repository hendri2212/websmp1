<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Berita dan Pengumuman</span>
        <h1>Informasi terbaru dari <?= e($content['schoolProfile']['shortName']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['berita'])): ?>
        <p><?= e($content['pageIntroductions']['berita']); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container card-grid two">
        <?php if (!$news): ?>
            <p class="muted">Belum ada berita yang dipublikasikan.</p>
        <?php endif; ?>
        <?php foreach ($news as $item): ?>
            <article class="card">
                <?php if ($item['image_path'] !== ''): ?>
                <img class="card-cover" src="<?= e(image_asset_path($item['image_path'])); ?>" alt="<?= e($item['title']); ?>" loading="lazy">
                <?php endif; ?>
                <small><?= e($item['category']); ?> - <?= e($item['date']); ?></small>
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
                <a class="text-link" href="<?= e(base_url('berita/' . rawurlencode($item['slug']))); ?>">Baca selengkapnya</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
