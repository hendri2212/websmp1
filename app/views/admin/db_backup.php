<div class="admin-card">
    <h1>Cadangan Data</h1>
    <p>Unduh salinan data website dan simpan di tempat aman. Pilih cadangan lengkap untuk menyertakan gambar serta dokumen yang telah diunggah.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!$pdo): ?><div class="alert warning">Cadangan belum dapat dibuat. Silakan coba lagi.</div><?php endif; ?>
    <?php if (!class_exists('ZipArchive')): ?><div class="alert warning">Cadangan lengkap belum tersedia. Anda tetap dapat mengunduh cadangan data.</div><?php endif; ?>
    <form method="post" class="action-bar">
        <?= csrf_field(); ?>
        <button class="button primary blue" name="backup_type" value="sql" type="submit" <?= $pdo ? '' : 'disabled'; ?>>Unduh Cadangan Data</button>
        <button class="button secondary" name="backup_type" value="full" type="submit" <?= $pdo && class_exists('ZipArchive') ? '' : 'disabled'; ?>>Unduh Cadangan Lengkap</button>
    </form>
</div>
