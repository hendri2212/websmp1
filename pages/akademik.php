<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Akademik</span>
        <h1>Bidang Akademik <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi mengenai kurikulum, pembelajaran, asesmen, kalender pendidikan, serta layanan pendampingan akademik bagi peserta didik dan orang tua.</p>
    </div>
</section>

<section class="section">
    <div class="container card-grid three">
        <?php foreach ($content['academicPrograms'] as $item): ?>
            <article class="card">
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
