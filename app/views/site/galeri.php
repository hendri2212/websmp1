<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Galeri</span>
        <h1>Dokumentasi kegiatan <?= e($content['schoolProfile']['shortName']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['galeri'])): ?>
        <p><?= e($content['pageIntroductions']['galeri']); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container card-grid two">
        <?php foreach ($gallery as $item): ?>
            <article class="card gallery-card">
                <img src="<?= e(image_asset_path($item['image'])); ?>" alt="<?= e($item['title']); ?>" loading="lazy">
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['description']); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
