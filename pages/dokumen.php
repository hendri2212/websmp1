<?php
$selectedCategory = trim($_GET['category'] ?? '');
$categories = document_categories();
$documents = public_documents($selectedCategory !== '' ? $selectedCategory : null);
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Dokumen</span>
        <h1>Download Dokumen <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Unduh kalender akademik, formulir, surat edaran, tata tertib, dan dokumen resmi sekolah dalam format PDF.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Arsip PDF</span>
            <h2>Dokumen resmi sekolah</h2>
            <p>Pilih kategori untuk memudahkan pencarian dokumen tahun ajaran <?= e(current_academic_year()); ?>.</p>
        </div>
        <?php if ($categories): ?>
            <div class="document-filter">
                <a class="button <?= $selectedCategory === '' ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('dokumen')); ?>">Semua</a>
                <?php foreach ($categories as $category): ?>
                    <a class="button <?= $selectedCategory === $category ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('dokumen?category=' . rawurlencode($category))); ?>"><?= e($category); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$documents): ?>
            <article class="panel">
                <p class="muted">Belum ada dokumen yang tersedia untuk diunduh.</p>
            </article>
        <?php else: ?>
            <div class="document-grid">
                <?php foreach ($documents as $document): ?>
                    <article class="document-card">
                        <span class="eyebrow"><?= e($document['category']); ?></span>
                        <h3><?= e($document['title']); ?></h3>
                        <p><?= e($document['description']); ?></p>
                        <p class="muted"><?= e($document['original_filename']); ?> - <?= number_format(((int) $document['file_size']) / 1024, 1); ?> KB</p>
                        <a class="button primary blue" href="<?= e(base_url('dokumen/unduh?id=' . (int) $document['id'])); ?>">Unduh PDF</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
