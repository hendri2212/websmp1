<div class="admin-card">
    <h1>Backup Database</h1>
    <p>Unduh salinan database atau paket lengkap berisi database dan file upload. Simpan file backup di tempat aman sebelum update besar, migrasi hosting, atau publikasi website.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!$pdo): ?><div class="alert warning">Database belum terhubung. Jalankan MySQL dan cek konfigurasi database terlebih dahulu.</div><?php endif; ?>
    <?php if (!class_exists('ZipArchive')): ?><div class="alert warning">Ekstensi PHP zip belum aktif. Backup SQL tetap bisa digunakan, tetapi paket backup lengkap ZIP belum tersedia.</div><?php endif; ?>
    <form method="post" class="action-bar">
        <?= csrf_field(); ?>
        <button class="button primary blue" name="backup_type" value="sql" type="submit" <?= $pdo ? '' : 'disabled'; ?>>Unduh Backup SQL</button>
        <button class="button secondary" name="backup_type" value="full" type="submit" <?= $pdo && class_exists('ZipArchive') ? '' : 'disabled'; ?>>Unduh Paket Lengkap ZIP</button>
    </form>
    <p class="muted">Paket lengkap ZIP berisi file SQL, schema, folder <code>public/uploads</code>, folder <code>storage/uploads</code>, dan <code>manifest.json</code>.</p>
</div>

<div class="admin-card">
    <h2>Ringkasan Tabel</h2>
    <?php if (!$tableSummaries): ?>
        <p class="muted">Belum ada tabel yang bisa dibaca.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nama Tabel</th><th>Jumlah Data</th></tr></thead>
                <tbody>
                <?php foreach ($tableSummaries as $summary): ?>
                    <tr>
                        <td><?= e($summary['table']); ?></td>
                        <td><?= number_format((int) $summary['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
