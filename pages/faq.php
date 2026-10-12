<?php
$selectedCategory = trim($_GET['category'] ?? '');
$categories = faq_categories();
$faqRows = faqs($selectedCategory !== '' ? $selectedCategory : null);
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">FAQ</span>
        <h1>Pertanyaan Umum <?= e($content['schoolProfile']['shortName']); ?></h1>
        <p>Temukan jawaban singkat mengenai PPDB, administrasi, akademik, layanan sekolah, dan informasi umum lainnya.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Bantuan Cepat</span>
            <h2>Informasi yang sering ditanyakan</h2>
            <p>FAQ dapat diperbarui melalui panel admin sehingga pengunjung mendapat informasi resmi yang konsisten.</p>
        </div>

        <?php if ($categories): ?>
            <div class="document-filter">
                <a class="button <?= $selectedCategory === '' ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('faq')); ?>">Semua</a>
                <?php foreach ($categories as $category): ?>
                    <a class="button <?= $selectedCategory === $category ? 'primary blue' : 'secondary'; ?>" href="<?= e(base_url('faq?category=' . rawurlencode($category))); ?>"><?= e($category); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$faqRows): ?>
            <article class="panel">
                <p class="muted">FAQ belum tersedia. Silakan hubungi sekolah melalui halaman kontak untuk pertanyaan lebih lanjut.</p>
            </article>
        <?php else: ?>
            <div class="faq-list">
                <?php foreach ($faqRows as $faq): ?>
                    <details class="faq-item">
                        <summary>
                            <span><?= e($faq['question']); ?></span>
                            <small><?= e($faq['category']); ?></small>
                        </summary>
                        <p><?= nl2br(e($faq['answer'])); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
