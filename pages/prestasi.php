<?php
$selectedCategory = trim($_GET['category'] ?? '');
<<<<<<< HEAD
$categories = achievement_categories_all_years();
$achievementRows = achievement_rows_all_years($selectedCategory !== '' ? $selectedCategory : null);
=======
$categories = achievement_categories();
$achievementRows = achievements($selectedCategory !== '' ? $selectedCategory : null);
>>>>>>> 98147e3 (upload pertama)
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Prestasi</span>
        <h1>Prestasi <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Dokumentasi capaian peserta didik, guru, dan sekolah dalam bidang akademik maupun non-akademik.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Kabar Baik</span>
            <h2>Jejak prestasi sekolah</h2>
<<<<<<< HEAD
            <p>Daftar prestasi yang dipublikasikan melalui panel admin agar informasi pencapaian sekolah tetap relevan.</p>
=======
            <p>Daftar prestasi tahun ajaran <?= e(current_academic_year()); ?> dapat diperbarui melalui panel admin agar informasi pencapaian sekolah tetap relevan.</p>
>>>>>>> 98147e3 (upload pertama)
        </div>

        <?php if ($categories): ?>
            <div class="document-filter">
                <a class="button <?= $selectedCategory === '' ? 'primary blue' : 'secondary'; ?>" href="<?= base_url('prestasi'); ?>">Semua</a>
                <?php foreach ($categories as $category): ?>
                    <a class="button <?= $selectedCategory === $category ? 'primary blue' : 'secondary'; ?>" href="<?= base_url('prestasi?category=' . rawurlencode($category)); ?>"><?= e($category); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$achievementRows): ?>
            <article class="panel">
                <p class="muted">Data prestasi sekolah belum tersedia.</p>
            </article>
        <?php else: ?>
            <div class="achievement-grid">
                <?php foreach ($achievementRows as $achievement): ?>
                    <article class="achievement-card">
                        <?php if (is_placeholder_image($achievement['image_path'] ?? null)): ?>
                            <div class="visual-placeholder wide-placeholder" aria-hidden="true">
                                <span><?= e(initials_from_text($achievement['title'])); ?></span>
                                <small><?= e($achievement['category']); ?></small>
                            </div>
                        <?php else: ?>
                            <img src="<?= image_asset_path($achievement['image_path']); ?>" alt="<?= e($achievement['title']); ?>" loading="lazy">
                        <?php endif; ?>
                        <div>
                            <span class="eyebrow"><?= e($achievement['category']); ?> - <?= e($achievement['level']); ?></span>
                            <h3><?= e($achievement['title']); ?></h3>
                            <p class="staff-role"><?= e($achievement['achiever_name']); ?></p>
                            <p class="muted"><?= date('d F Y', strtotime($achievement['achievement_date'])); ?></p>
                            <p><?= e($achievement['description']); ?></p>
<<<<<<< HEAD
                            <a class="text-link" href="<?= base_url('prestasi/' . (int) $achievement['id']); ?>">Baca selengkapnya</a>
=======
>>>>>>> 98147e3 (upload pertama)
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
