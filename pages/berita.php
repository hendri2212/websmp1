<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Berita dan Pengumuman</span>
        <h1>Informasi terbaru dari <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Pusat publikasi sekolah untuk menyampaikan berita, pengumuman, dan dokumentasi kegiatan secara resmi kepada warga sekolah dan masyarakat.</p>
    </div>
</section>

<section class="section">
    <div class="container card-grid two">
        <?php foreach ($news as $item): ?>
            <article class="card">
                <img class="card-cover" src="<?= image_asset_path($item['image_path']); ?>" alt="<?= e($item['title']); ?>" loading="lazy">
                <small><?= e($item['category']); ?> - <?= e($item['date']); ?></small>
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
                <a class="text-link" href="<?= base_url('berita/' . $item['slug']); ?>">Baca selengkapnya</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
