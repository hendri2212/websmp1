<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Maintenance</span>
        <h1>Website Sedang Dalam Pemeliharaan</h1>
        <p class="hero-text"><?= e($content['siteMaintenance']['message']); ?></p>
    </div>
</section>

<section class="section">
    <div class="container narrow-content">
        <div class="panel">
            <h2>Layanan Admin Tetap Aktif</h2>
            <?php if ($content['siteMaintenance']['details'] !== ''): ?>
            <p><?= e($content['siteMaintenance']['details']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
