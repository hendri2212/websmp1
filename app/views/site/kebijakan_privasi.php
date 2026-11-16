<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Privasi</span>
        <h1>Kebijakan Privasi <?= e($content['schoolProfile']['shortName']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['kebijakan_privasi'])): ?>
        <p><?= e($content['pageIntroductions']['kebijakan_privasi']); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container narrow-content">
        <?php foreach ($content['privacySections'] as $section): ?>
        <article class="panel">
            <h2><?= e($section['title']); ?></h2>
            <?php foreach ($section['paragraphs'] as $paragraph): ?>
            <p><?= e($paragraph); ?></p>
            <?php endforeach; ?>
        </article>
        <?php endforeach; ?>
    </div>
</section>
