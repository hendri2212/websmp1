<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Akademik</span>
        <h1>Bidang Akademik <?= e($content['schoolProfile']['shortName']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['akademik'])): ?>
        <p><?= e($content['pageIntroductions']['akademik']); ?></p>
        <?php endif; ?>
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
