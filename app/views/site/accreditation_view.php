<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Akreditasi</span>
        <h1>Lembar Akreditasi Sekolah</h1>
        <p>Dokumen ini ditampilkan sebagai informasi resmi untuk pengunjung website.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <article class="panel accreditation-view-panel">
            <div class="section-heading">
                <span class="eyebrow">Status Akreditasi <?= e($status !== '' ? $status : 'A'); ?></span>
                <h2><?= e($content['schoolProfile']['name']); ?></h2>
                <p><?= e($accreditation['description'] ?? 'Lembar akreditasi resmi sekolah dapat dilihat pada area pratinjau dokumen.'); ?></p>
            </div>
            <div class="meta-list accreditation-meta">
                <?php if ($skNumber !== ''): ?><span>Nomor SK: <?= e($skNumber); ?></span><?php endif; ?>
                <?php if ($skDate !== ''): ?><span>Tanggal SK: <?= e($skDate); ?></span><?php endif; ?>
            </div>

            <?php if ($filePath !== ''): ?>
                <div class="pdf-viewer-frame" oncontextmenu="return false">
                    <iframe src="<?= e(base_url('akreditasi/file')); ?>#toolbar=0&navpanes=0&scrollbar=1" title="Lembar akreditasi sekolah" loading="lazy"></iframe>
                </div>
                <p class="muted">File ditampilkan untuk dibaca di website. Tombol unduh tidak disediakan pada halaman ini.</p>
            <?php else: ?>
                <div class="visual-placeholder wide-placeholder">
                    <span>PDF</span>
                    <small>Lembar akreditasi belum diunggah admin</small>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>
