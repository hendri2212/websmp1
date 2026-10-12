<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse"><?= e($achievementItem['category']); ?> - <?= e($achievementItem['level']); ?></span>
        <h1><?= e($achievementItem['title']); ?></h1>
        <p><?= e(date('d F Y', strtotime($achievementItem['achievement_date']))); ?> - <?= e($achievementItem['achiever_name']); ?></p>
    </div>
</section>

<?php $relatedAchievements = related_achievements((int) $achievementItem['id'], $achievementItem['category'], 3); ?>
<section class="section">
    <div class="container narrow-content">
        <?php if (!is_placeholder_image($achievementItem['image_path'] ?? null)): ?>
            <img class="feature-image-photo" src="<?= e(image_asset_path($achievementItem['image_path'])); ?>" alt="<?= e($achievementItem['title']); ?>">
        <?php endif; ?>
        <article class="panel">
            <span class="eyebrow"><?= e($achievementItem['category']); ?></span>
            <h2><?= e($achievementItem['title']); ?></h2>
            <p class="lead"><?= e($achievementItem['achiever_name']); ?> meraih prestasi pada <?= e(date('d F Y', strtotime($achievementItem['achievement_date']))); ?>.</p>
            <?php foreach (preg_split('/\R+/', trim($achievementItem['description'])) as $paragraph): ?>
                <?php if (trim($paragraph) !== ''): ?>
                    <p><?= e($paragraph); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <a class="text-link" href="<?= e(base_url('prestasi')); ?>">Kembali ke daftar prestasi</a>
        </article>
    </div>
</section>

<?php if ($relatedAchievements): ?>
<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Prestasi Terkait</span>
            <h2>Capaian lain yang bisa dibaca</h2>
        </div>
        <div class="achievement-grid related-grid">
            <?php foreach ($relatedAchievements as $item): ?>
                <article class="achievement-card compact-card">
                    <?php if (is_placeholder_image($item['image_path'] ?? null)): ?>
                        <div class="visual-placeholder" aria-hidden="true">
                            <span><?= e(initials_from_text($item['title'])); ?></span>
                        </div>
                    <?php else: ?>
                        <img src="<?= e(image_asset_path($item['image_path'])); ?>" alt="<?= e($item['title']); ?>" loading="lazy">
                    <?php endif; ?>
                    <span class="eyebrow"><?= e($item['category']); ?> - <?= e($item['level']); ?></span>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['achiever_name']); ?></p>
                    <small><?= e(date('d F Y', strtotime($item['achievement_date']))); ?></small>
                    <a class="text-link" href="<?= e(base_url('prestasi/' . (int) $item['id'])); ?>">Lihat prestasi</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
