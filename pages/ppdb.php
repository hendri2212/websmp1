<?php
$ppdbSections = ppdb_sections_data();
$ppdbDocuments = public_documents('PPDB');
$ppdbContactItems = $ppdbSections['kontak'] ?? [];
if (!$ppdbContactItems) {
    $ppdbContactItems = array_filter($content['contactChannels'] ?? [], static function (array $item): bool {
        return in_array($item['title'] ?? '', ['Telepon', 'Email Resmi', 'Jam Layanan'], true);
    });
}
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">PPDB</span>
        <h1>Informasi PPDB <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Pusat informasi penerimaan peserta didik baru tahun ajaran <?= e(current_academic_year()); ?>: jalur, alur, persyaratan, edaran resmi, dan kontak panitia.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Sistem Informasi</span>
            <h2>PPDB sekolah sebagai pusat rujukan informasi</h2>
            <p>Pendaftaran online mengikuti kanal resmi Dinas Pendidikan atau sistem yang ditetapkan panitia. Website sekolah ini difokuskan untuk membantu orang tua membaca informasi, menyiapkan dokumen, dan menghubungi panitia.</p>
        </div>
        <div class="ppdb-info-panel">
            <article class="panel dark">
                <span class="eyebrow inverse">Penting</span>
                <h3>Pendaftaran tidak dilakukan melalui website ini</h3>
                <p>Gunakan tautan, edaran, atau pengumuman resmi yang diterbitkan Dinas Pendidikan dan panitia PPDB. Informasi pada halaman ini dapat diperbarui sesuai kebijakan terbaru.</p>
            </article>
            <div class="card-grid two">
                <?php foreach ($ppdbSections['informasi'] ?? [] as $item): ?>
                    <article class="card">
                        <h3><?= e($item['title']); ?></h3>
                        <p><?= e($item['desc']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Jalur PPDB</span>
            <h2>Kriteria dan jalur yang perlu dipahami</h2>
            <p>Ketentuan jalur mengikuti aturan resmi yang berlaku pada tahun ajaran berjalan.</p>
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
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Alur PPDB</span>
            <h2>Tahapan informasi dan layanan panitia</h2>
        </div>
        <div class="card-grid two">
            <?php foreach ($content['ppdbSteps'] as $index => $step): ?>
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
                        <a class="button primary blue" href="<?= base_url('dokumen/unduh?id=' . (int) $document['id']); ?>">Unduh PDF</a>
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
