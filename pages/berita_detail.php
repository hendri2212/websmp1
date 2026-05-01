<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse"><?= e($newsItem['category']); ?></span>
        <h1><?= e($newsItem['title']); ?></h1>
        <p><?= e($newsItem['date']); ?> - Publikasi resmi <?= e($content['schoolProfile']['shortName']); ?></p>
    </div>
</section>

<<<<<<< HEAD
<?php $relatedNews = related_news_items($newsItem['slug'], $newsItem['category'], 3); ?>
=======
>>>>>>> 98147e3 (upload pertama)
<section class="section">
    <div class="container narrow-content">
        <img class="feature-image-photo" src="<?= asset(ltrim($newsItem['image_path'], '/')); ?>" alt="<?= e($newsItem['title']); ?>">
        <article class="panel">
            <p class="lead"><?= e($newsItem['desc']); ?></p>
            <?php foreach ($newsItem['content'] as $paragraph): ?>
                <p><?= e($paragraph); ?></p>
            <?php endforeach; ?>
            <a class="button primary" href="<?= base_url('berita'); ?>">Kembali ke Semua Berita</a>
        </article>
    </div>
</section>
<<<<<<< HEAD

<?php if ($relatedNews): ?>
<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Berita Terkait</span>
            <h2>Publikasi lain yang relevan</h2>
        </div>
        <div class="card-grid three related-grid">
            <?php foreach ($relatedNews as $item): ?>
                <article class="card compact-card">
                    <img class="card-cover" src="<?= image_asset_path($item['image_path']); ?>" alt="<?= e($item['title']); ?>" loading="lazy">
                    <small><?= e($item['category']); ?> - <?= e($item['date']); ?></small>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e(plain_text_excerpt($item['desc'], 110)); ?></p>
                    <a class="text-link" href="<?= base_url('berita/' . $item['slug']); ?>">Baca selengkapnya</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
=======
>>>>>>> 98147e3 (upload pertama)
