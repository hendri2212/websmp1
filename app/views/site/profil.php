<section class="page-hero profile-hero">
    <div class="container">
        <span class="eyebrow inverse">Profil</span>
        <h1>Profil Resmi <?= e($content['schoolProfile']['name']); ?></h1>
        <?php if (!empty($content['pageIntroductions']['profil'])): ?>
        <p><?= e($content['pageIntroductions']['profil']); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php
$accreditation = $content['schoolAccreditation'] ?? [];
$accreditationStatus = trim((string) ($accreditation['status'] ?? ''));
$accreditationSkNumber = trim((string) ($accreditation['skNumber'] ?? ''));
$accreditationSkDate = trim((string) ($accreditation['skDate'] ?? ''));
$accreditationFile = trim((string) ($accreditation['filePath'] ?? ''));
?>
<section class="section accreditation-section">
    <div class="container">
        <article class="panel accreditation-panel">
            <div class="accreditation-mark">
                <span>Akreditasi</span>
                <strong><?= e($accreditationStatus); ?></strong>
            </div>
            <div class="accreditation-copy">
                <span class="eyebrow">Legalitas Mutu Sekolah</span>
                <h2>Lembar akreditasi dapat dilihat pengunjung</h2>
                <p><?= e($accreditation['description'] ?? 'Informasi akreditasi sekolah disajikan sebagai bukti mutu dan legalitas layanan pendidikan.'); ?></p>
                <div class="meta-list accreditation-meta">
                    <?php if ($accreditationSkNumber !== ''): ?><span>Nomor SK: <?= e($accreditationSkNumber); ?></span><?php endif; ?>
                    <?php if ($accreditationSkDate !== ''): ?><span>Tanggal SK: <?= e($accreditationSkDate); ?></span><?php endif; ?>
                </div>
                <?php if ($accreditationFile !== ''): ?>
                    <a class="button primary blue" href="<?= e(base_url('akreditasi/lihat')); ?>">Lihat Lembar Akreditasi</a>
                <?php else: ?>
                    <p class="muted">Lembar akreditasi PDF belum diunggah oleh admin.</p>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<?php if ($content['principalMessage']['name'] !== '' || $content['principalMessage']['greeting'] !== ''): ?>
<?php $principalPhotoPath = $content['principalMessage']['photoPath'] ?? ''; ?>
<section class="section profile-principal-section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Sambutan Kepala Sekolah</span>
            <h2>Pesan pengantar dari pimpinan sekolah</h2>
        </div>
        <div class="two-grid narrow profile-principal-grid">
            <article class="panel photo-panel profile-photo-panel">
                <?php if ($principalPhotoPath !== ''): ?>
                <img class="portrait" src="<?= e(asset(ltrim($principalPhotoPath, '/'))); ?>" alt="Kepala sekolah">
                <?php endif; ?>
                <strong><?= e($content['principalMessage']['name']); ?></strong>
                <span><?= e($content['principalMessage']['title']); ?></span>
            </article>
            <article class="panel profile-message-panel">
                <h3><?= e($content['principalMessage']['greeting']); ?></h3>
                <?php foreach ($content['principalMessage']['paragraphs'] as $paragraph): ?>
                    <p><?= e($paragraph); ?></p>
                <?php endforeach; ?>
            </article>
        </div>
    </div>
</section>

<?php endif; ?>

<section class="section alt profile-sections-band">
    <div class="container">
        <div class="section-head-row compact">
            <div class="section-heading">
                <span class="eyebrow">Profil Sekolah</span>
                <h2>Informasi kelembagaan</h2>
            </div>
            <div class="carousel-actions" aria-label="Navigasi profil sekolah">
                <button class="carousel-button" type="button" data-carousel-prev="profile-section-carousel" aria-label="Geser profil ke kiri">&#8249;</button>
                <button class="carousel-button" type="button" data-carousel-next="profile-section-carousel" aria-label="Geser profil ke kanan">&#8250;</button>
            </div>
        </div>
        <div class="content-carousel profile-section-carousel" id="profile-section-carousel">
            <?php foreach ($content['profileSections'] as $section): ?>
                <article class="card content-slide profile-info-card">
                    <h3><?= e($section['title']); ?></h3>
                    <p><?= e($section['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section profile-data-section">
    <div class="container two-grid">
        <article class="panel profile-data-panel">
            <div class="panel-carousel-head">
                <div class="section-heading">
                    <span class="eyebrow">Identitas Sekolah</span>
                    <h2>Data Dasar Sekolah</h2>
                </div>
                <div class="carousel-actions small-actions" aria-label="Navigasi identitas sekolah">
                    <button class="carousel-button small-carousel-button" type="button" data-carousel-up="school-identity-carousel" aria-label="Geser identitas ke atas">&#8593;</button>
                    <button class="carousel-button small-carousel-button" type="button" data-carousel-down="school-identity-carousel" aria-label="Geser identitas ke bawah">&#8595;</button>
                </div>
            </div>
            <ul class="feature-list plain vertical-carousel" id="school-identity-carousel">
                <?php foreach ($content['schoolIdentity'] as $item): ?>
                    <li><strong><?= e($item['label']); ?>:</strong> <?= e($item['value']); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="panel profile-data-panel">
            <div class="panel-carousel-head">
                <div class="section-heading">
                    <span class="eyebrow">Sarana dan Prasarana</span>
                    <h2>Fasilitas pendukung kegiatan sekolah</h2>
                </div>
                <div class="carousel-actions small-actions" aria-label="Navigasi fasilitas sekolah">
                    <button class="carousel-button small-carousel-button" type="button" data-carousel-up="school-facilities-carousel" aria-label="Geser fasilitas ke atas">&#8593;</button>
                    <button class="carousel-button small-carousel-button" type="button" data-carousel-down="school-facilities-carousel" aria-label="Geser fasilitas ke bawah">&#8595;</button>
                </div>
            </div>
            <ul class="feature-list plain vertical-carousel" id="school-facilities-carousel">
                <?php foreach (school_facilities() as $item): ?>
                    <li><?= e($item['name']); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a class="text-link" href="<?= e(base_url('fasilitas')); ?>">Lihat detail fasilitas sekolah</a></p>
        </article>
    </div>
</section>
