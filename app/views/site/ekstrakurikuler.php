<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Ekstrakurikuler</span>
        <h1>Ekstrakurikuler <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Wadah pengembangan minat, bakat, kepemimpinan, sportivitas, kreativitas, dan karakter peserta didik.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Minat & Bakat</span>
            <h2>Pilihan kegiatan ekstrakurikuler</h2>
            <p>Informasi ekskul dapat diperbarui melalui panel admin, termasuk pembina, jadwal, lokasi, dan deskripsi kegiatan.</p>
        </div>

        <?php if ($categories): ?>
            <div class="document-filter">
                <a class="button <?= $selectedCategory === '' ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('ekstrakurikuler')); ?>">Semua</a>
                <?php foreach ($categories as $category): ?>
                    <a class="button <?= $selectedCategory === $category ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('ekstrakurikuler?category=' . rawurlencode($category))); ?>"><?= e($category); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$extracurricularRows): ?>
            <article class="panel">
                <p class="muted">Data ekstrakurikuler belum tersedia.</p>
            </article>
        <?php else: ?>
            <div class="extracurricular-grid">
                <?php foreach ($extracurricularRows as $item): ?>
                    <article class="extracurricular-card">
                        <?php if (is_placeholder_image($item['image_path'] ?? null)): ?>
                            <div class="visual-placeholder wide-placeholder" aria-hidden="true">
                                <span><?= e(initials_from_text($item['name'])); ?></span>
                                <small><?= e($item['category']); ?></small>
                            </div>
                        <?php else: ?>
                            <img src="<?= e(image_asset_path($item['image_path'])); ?>" alt="<?= e($item['name']); ?>" loading="lazy">
                        <?php endif; ?>
                        <div>
                            <span class="eyebrow"><?= e($item['category']); ?></span>
                            <h3><?= e($item['name']); ?></h3>
                            <p><?= e($item['description']); ?></p>
                            <?php if (!empty($item['coach']) || !empty($item['schedule_text']) || !empty($item['location'])): ?>
                                <div class="meta-list">
                                    <?php if (!empty($item['coach'])): ?><span>Pembina: <?= e($item['coach']); ?></span><?php endif; ?>
                                    <?php if (!empty($item['schedule_text'])): ?><span>Jadwal: <?= e($item['schedule_text']); ?></span><?php endif; ?>
                                    <?php if (!empty($item['location'])): ?><span>Lokasi: <?= e($item['location']); ?></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
