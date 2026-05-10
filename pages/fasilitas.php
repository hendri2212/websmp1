<?php
$selectedCategory = trim($_GET['category'] ?? '');
$categories = facility_categories();
$facilityRows = school_facilities($selectedCategory !== '' ? $selectedCategory : null);
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Fasilitas</span>
        <h1>Fasilitas <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi sarana dan prasarana sekolah yang mendukung proses belajar, kegiatan siswa, dan layanan pendidikan, dilengkapi foto agar kondisi fasilitas lebih mudah dilihat.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Sarana & Prasarana</span>
            <h2>Lingkungan belajar yang mendukung</h2>
            <p>Setiap fasilitas dapat ditampilkan dengan foto, lokasi, kapasitas, kondisi, dan deskripsi sehingga pengunjung tidak hanya membaca daftar fasilitas, tetapi juga melihat gambaran visualnya.</p>
        </div>

        <?php if ($categories): ?>
            <div class="document-filter">
                <a class="button <?= $selectedCategory === '' ? 'primary blue' : 'secondary'; ?>" href="<?= base_url('fasilitas'); ?>">Semua</a>
                <?php foreach ($categories as $category): ?>
                    <a class="button <?= $selectedCategory === $category ? 'primary blue' : 'secondary'; ?>" href="<?= base_url('fasilitas?category=' . rawurlencode($category)); ?>"><?= e($category); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$facilityRows): ?>
            <article class="panel">
                <p class="muted">Data fasilitas sekolah belum tersedia.</p>
            </article>
        <?php else: ?>
            <div class="facility-grid">
                <?php foreach ($facilityRows as $facility): ?>
                    <article class="facility-card">
                        <?php if (is_placeholder_image($facility['image_path'] ?? null)): ?>
                            <div class="visual-placeholder wide-placeholder" aria-hidden="true">
                                <span><?= e(initials_from_text($facility['name'])); ?></span>
                                <small><?= e($facility['category']); ?></small>
                            </div>
                        <?php else: ?>
                            <img src="<?= image_asset_path($facility['image_path']); ?>" alt="<?= e($facility['name']); ?>" loading="lazy" draggable="false" oncontextmenu="return false">
                        <?php endif; ?>
                        <div>
                            <span class="eyebrow"><?= e($facility['category']); ?></span>
                            <h3><?= e($facility['name']); ?></h3>
                            <p><?= e($facility['description']); ?></p>
                            <div class="meta-list">
                                <span>Kondisi: <?= e($facility['condition_status'] ?? 'Baik'); ?></span>
                                <?php if (!empty($facility['location'])): ?><span>Lokasi: <?= e($facility['location']); ?></span><?php endif; ?>
                                <?php if (!empty($facility['capacity'])): ?><span>Kapasitas: <?= e($facility['capacity']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
