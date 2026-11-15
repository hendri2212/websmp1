<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Guru & Staf</span>
        <h1>Data Guru dan Staf <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi pendidik dan tenaga kependidikan sekolah sebagai bagian dari keterbukaan layanan pendidikan kepada peserta didik, orang tua, dan masyarakat.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Tim Sekolah</span>
            <h2>Pendidik dan tenaga kependidikan</h2>
            <p>Daftar ini dapat diperbarui melalui panel admin sesuai data resmi sekolah.</p>
        </div>
        <?php if (!$staffMembers): ?>
            <article class="panel">
                <p class="muted">Data guru dan staf belum tersedia.</p>
            </article>
        <?php else: ?>
            <div class="staff-grid">
                <?php foreach ($staffMembers as $staff): ?>
                    <article class="staff-card">
                        <?php if (is_placeholder_image($staff['photo_path'] ?? null)): ?>
                            <div class="visual-placeholder staff-placeholder" aria-hidden="true">
                                <span><?= e(initials_from_text($staff['name'])); ?></span>
                            </div>
                        <?php else: ?>
                            <img src="<?= e(image_asset_path($staff['photo_path'])); ?>" alt="<?= e($staff['name']); ?>" loading="lazy">
                        <?php endif; ?>
                        <div>
                            <h3><?= e($staff['name']); ?></h3>
                            <p class="staff-role"><?= e($staff['position']); ?></p>
                            <p class="muted"><?= e($staff['subject']); ?></p>
                            <p><?= e($staff['bio']); ?></p>
                            <?php if (!empty($staff['email']) || !empty($staff['phone'])): ?>
                                <p class="muted"><?= e(trim(($staff['email'] ?? '') . ' ' . ($staff['phone'] ?? ''))); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
