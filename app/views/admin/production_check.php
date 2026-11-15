<div class="admin-card">
    <h1>Cek Kesiapan Production</h1>
    <p>Gunakan halaman ini sebelum publish ke hosting untuk memeriksa konfigurasi penting, koneksi database, keamanan dasar, dan folder upload.</p>

    <div class="action-bar">
        <span class="status-badge status-diterima"><?= (int) $summary['ok']; ?> OK</span>
        <span class="status-badge status-diproses"><?= (int) $summary['warning']; ?> perlu dicek</span>
        <span class="status-badge status-ditolak"><?= (int) $summary['error']; ?> masalah</span>
        <a class="button secondary" href="<?= e(base_url('admin/db_backup.php')); ?>">Backup Database</a>
        <a class="button secondary" href="<?= e(base_url('admin/change_password.php')); ?>">Ganti Password</a>
    </div>

    <?php if ($summary['error'] > 0): ?>
        <div class="alert error">Ada item penting yang perlu diperbaiki sebelum website dipublikasikan.</div>
    <?php elseif ($summary['warning'] > 0): ?>
        <div class="alert warning">Tidak ada masalah kritis, tetapi masih ada konfigurasi yang perlu dicek sebelum live.</div>
    <?php else: ?>
        <div class="alert success">Semua pemeriksaan utama sudah terlihat siap untuk production.</div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>Hasil Pemeriksaan</h2>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Detail</th>
                    <th>Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                    <tr>
                        <td><strong><?= e($check['name']); ?></strong></td>
                        <td><span class="status-badge <?= production_check_status($check['status']); ?>"><?= e(production_check_label($check['status'])); ?></span></td>
                        <td><?= e($check['detail']); ?></td>
                        <td><?= e($check['recommendation']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
