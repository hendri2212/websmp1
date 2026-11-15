<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">PPDB</span>
        <h1>Penerimaan Peserta Didik Baru <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Informasi penerimaan peserta didik baru tahun ajaran <?= e(current_academic_year()); ?>. Pendaftaran resmi ditangani Dinas Pendidikan, bukan melalui website sekolah ini.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Kriteria Pendaftaran</span>
            <h2>Jalur PPDB yang tersedia</h2>
            <p>Pilih jalur pendaftaran sesuai kondisi calon peserta didik dan siapkan dokumen pendukung yang diminta panitia.</p>
        </div>
        <div class="ppdb-criteria-grid">
            <?php foreach (($ppdbSections['kriteria'] ?? []) as $item): ?>
                <article class="card ppdb-criteria-card">
                    <span><?= e(substr($item['title'], 0, 1)); ?></span>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['desc']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container card-grid three">
        <?php foreach ($ppdbSections['informasi'] ?? [] as $item): ?>
            <article class="card">
                <h3><?= e($item['title']); ?></h3>
                <p><?= e($item['desc']); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Alur PPDB</span>
            <h2>Tahapan umum penerimaan peserta didik baru</h2>
        </div>
        <div class="card-grid two">
            <?php foreach ($ppdbSteps as $index => $step): ?>
                <article class="timeline-item">
                    <span><?= $index + 1; ?></span>
                    <div><p><?= e($step); ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Edaran PPDB</span>
            <h2>Dokumen dan pengumuman resmi</h2>
            <p>Unduh surat edaran, jadwal, formulir informasi, atau dokumen PPDB lain yang diterbitkan sekolah.</p>
        </div>
        <?php if ($ppdbDocuments): ?>
            <div class="document-grid">
                <?php foreach ($ppdbDocuments as $document): ?>
                    <article class="document-card">
                        <span class="eyebrow"><?= e($document['category']); ?></span>
                        <h3><?= e($document['title']); ?></h3>
                        <p><?= e($document['description']); ?></p>
                        <p class="muted"><?= e($document['original_filename']); ?> - <?= number_format(((int) $document['file_size']) / 1024, 1); ?> KB</p>
                        <a class="button primary blue" href="<?= e(base_url('dokumen/unduh?id=' . (int) $document['id'])); ?>">Unduh PDF</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <article class="panel">
                <p class="muted">Edaran PPDB belum tersedia. Panitia dapat mengunggah dokumen dari menu Dokumen dengan kategori PPDB.</p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container two-grid">
        <article class="panel">
            <div class="section-heading">
                <span class="eyebrow">Persyaratan</span>
                <h2>Dokumen yang perlu disiapkan</h2>
            </div>
            <ul class="feature-list plain">
                <?php foreach (($ppdbSections['persyaratan'] ?? []) as $item): ?>
                    <li><?= e($item['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
        <article class="panel">
            <div class="section-heading">
                <span class="eyebrow">Catatan Penting</span>
                <h2>Hal yang perlu diperhatikan</h2>
            </div>
            <ul class="feature-list plain">
                <?php foreach (($ppdbSections['catatan'] ?? []) as $item): ?>
                    <li><?= e($item['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Kontak Panitia</span>
            <h2>Layanan informasi PPDB</h2>
            <p>Hubungi panitia pada jam layanan untuk memastikan jadwal, persyaratan, dan kanal pendaftaran resmi.</p>
        </div>
        <div class="card-grid three">
            <?php foreach ($ppdbContactItems as $item): ?>
                <article class="card">
                    <h3><?= e($item['title']); ?></h3>
                    <?php if (!empty($item['value'])): ?>
                        <p><strong><?= e($item['value']); ?></strong></p>
                    <?php endif; ?>
                    <?php if (!empty($item['desc'])): ?>
                        <p><?= e($item['desc']); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
