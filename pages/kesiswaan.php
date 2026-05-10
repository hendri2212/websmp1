<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Kesiswaan</span>
        <h1>Bidang Kesiswaan <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi mengenai organisasi siswa, kegiatan ekstrakurikuler, pembinaan karakter, dan pengembangan potensi peserta didik sebagai bagian dari budaya sekolah.</p>
    </div>
</section>

<section class="section">
    <div class="container card-grid three">
        <?php foreach ($content['studentPrograms'] as $item): ?>
            <article class="card">
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
                <?php if (strtolower($item['title']) === 'ekstrakurikuler'): ?>
                    <p><a class="text-link" href="<?= base_url('ekstrakurikuler'); ?>">Lihat daftar ekstrakurikuler</a></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
