<?php
$searchQuery = trim($_GET['q'] ?? '');
$searchResults = $searchQuery !== '' ? public_search_results($searchQuery) : [];
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow inverse">Pencarian</span>
        <h1>Cari informasi sekolah</h1>
        <p>Temukan berita, prestasi, agenda, dokumen, fasilitas, ekstrakurikuler, dan FAQ dari satu halaman.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form class="search-panel" action="<?= base_url('cari'); ?>" method="get">
            <label>
                <span>Kata kunci</span>
                <input type="search" name="q" value="<?= e($searchQuery); ?>" placeholder="Contoh: PPDB, prestasi, agenda, dokumen" autofocus>
            </label>
            <button class="button primary blue" type="submit">Cari</button>
        </form>

        <?php if ($searchQuery === ''): ?>
            <article class="panel search-empty">
                <h2>Masukkan kata kunci</h2>
                <p class="muted">Gunakan kolom pencarian untuk menemukan informasi penting di website sekolah.</p>
            </article>
        <?php elseif (!$searchResults): ?>
            <article class="panel search-empty">
                <h2>Belum ada hasil</h2>
                <p class="muted">Tidak ditemukan konten yang cocok dengan “<?= e($searchQuery); ?>”. Coba kata kunci lain yang lebih singkat.</p>
            </article>
        <?php else: ?>
            <div class="search-summary">
                <span class="eyebrow"><?= count($searchResults); ?> hasil</span>
                <h2>Hasil untuk “<?= e($searchQuery); ?>”</h2>
            </div>
            <div class="search-result-list">
                <?php foreach ($searchResults as $result): ?>
                    <article class="search-result-card">
                        <span class="eyebrow"><?= e($result['type']); ?></span>
                        <h3><a href="<?= e($result['url']); ?>"><?= e($result['title']); ?></a></h3>
                        <p><?= e(plain_text_excerpt($result['excerpt'], 180)); ?></p>
                        <small><?= e($result['meta']); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
