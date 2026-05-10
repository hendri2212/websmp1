<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('ppdb_applications');

$message = '';
$error = '';
$statusLabels = [
    'baru' => 'Baru',
    'diverifikasi' => 'Diverifikasi',
    'diterima' => 'Diterima',
    'ditolak' => 'Ditolak',
];
$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'academic_year' => trim($_GET['academic_year'] ?? current_academic_year()),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_ppdb_application((int) $_POST['delete_id']);
        $message = 'Data pendaftar berhasil dihapus.';
    } elseif (isset($_POST['application_id'], $_POST['status'])) {
        update_ppdb_application_status((int) $_POST['application_id'], trim($_POST['status']), trim($_POST['admin_notes'] ?? ''));
        $message = 'Status pendaftar berhasil diperbarui.';
    }
}

$rows = admin_ppdb_applications($filters);
$filteredCount = admin_ppdb_application_filtered_count($filters);
$exportUrl = base_url('admin/ppdb_export.php?' . http_build_query(array_filter($filters, static fn($value) => $value !== '')));
$pageTitle = 'Pendaftar PPDB';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Pendaftar PPDB Online</h1>
    <p>Kelola pendaftaran calon peserta didik baru, status verifikasi, dan berkas yang diunggah.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar data pendaftar aktif.</div><?php endif; ?>
</div>

<div class="admin-card">
    <h2>Daftar Pendaftar</h2>
    <form method="get" class="filter-bar">
        <label><span>Cari</span><input type="search" name="q" value="<?= e($filters['q']); ?>" placeholder="Nama, nomor, NISN, sekolah, wali"></label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Semua Status</option>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= e($value); ?>" <?= $filters['status'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($filters['academic_year']); ?>" placeholder="2025/2026"></label>
        <div class="filter-actions">
            <button class="button primary blue" type="submit">Terapkan</button>
            <a class="button secondary" href="<?= base_url('admin/ppdb_applications.php'); ?>">Reset</a>
            <a class="button secondary" href="<?= e($exportUrl); ?>">Export CSV/Excel</a>
        </div>
    </form>
    <p class="muted">Menampilkan <?= (int) $filteredCount; ?> pendaftar tahun ajaran <?= e($filters['academic_year']); ?><?= ($filters['q'] || $filters['status']) ? ' sesuai filter' : ''; ?>.</p>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada pendaftar PPDB online.</p>
    <?php else: ?>
        <div class="message-list">
            <?php foreach ($rows as $row): ?>
                <article class="message-item">
                    <div class="message-head">
                        <div>
                            <span class="status-badge status-<?= e($row['status']); ?>"><?= e($statusLabels[$row['status']] ?? $row['status']); ?></span>
                            <h3><?= e($row['student_name']); ?></h3>
                            <p class="muted">Jalur pendaftaran: <?= e(($row['registration_path'] ?? '') ?: '-'); ?></p>
                            <p class="muted"><?= e($row['registration_number']); ?> • <?= e($row['previous_school']); ?> • TA <?= e($row['academic_year'] ?? '-'); ?> • <?= e(date('d F Y H:i', strtotime($row['created_at']))); ?></p>
                            <p class="muted">Orang tua/wali: <?= e($row['parent_name']); ?> • <?= e($row['parent_phone']); ?> • <?= e($row['parent_email']); ?></p>
                        </div>
                        <form method="post" class="status-form ppdb-status-form">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="application_id" value="<?= (int) $row['id']; ?>">
                            <select name="status">
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?= e($value); ?>" <?= $row['status'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_notes" placeholder="Catatan admin"><?= e($row['admin_notes'] ?? ''); ?></textarea>
                            <button class="button secondary small" type="submit">Update</button>
                        </form>
                    </div>
                    <div class="detail-grid">
                        <p><strong>Tempat/Tanggal Lahir</strong><br><?= e($row['birth_place']); ?>, <?= e(date('d F Y', strtotime($row['birth_date']))); ?></p>
                        <p><strong>Jenis Kelamin</strong><br><?= e($row['gender']); ?></p>
                        <p><strong>NISN</strong><br><?= e($row['nisn'] ?: '-'); ?></p>
                        <p><strong>Jalur Pendaftaran</strong><br><?= e(($row['registration_path'] ?? '') ?: '-'); ?></p>
                        <p><strong>Alamat</strong><br><?= nl2br(e($row['address'])); ?></p>
                        <p><strong>Persetujuan Privasi</strong><br><?= !empty($row['consent_accepted_at']) ? e(date('d F Y H:i', strtotime($row['consent_accepted_at'])) . ' - Versi ' . ($row['privacy_version'] ?? '-')) : 'Belum tercatat'; ?></p>
                    </div>
                    <div class="action-bar">
                        <a class="button secondary small" href="<?= base_url('admin/ppdb_download.php?id=' . (int) $row['id'] . '&file=family_card'); ?>">Unduh KK</a>
                        <a class="button secondary small" href="<?= base_url('admin/ppdb_download.php?id=' . (int) $row['id'] . '&file=birth_certificate'); ?>">Unduh Akta</a>
                        <a class="button secondary small" href="<?= base_url('admin/ppdb_download.php?id=' . (int) $row['id'] . '&file=report_card'); ?>">Unduh Rapor</a>
                        <a class="button secondary small" href="<?= base_url('admin/ppdb_download.php?id=' . (int) $row['id'] . '&file=photo'); ?>">Unduh Foto</a>
                    </div>
                    <form method="post" onsubmit="return confirm('Hapus data pendaftar ini?');">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $row['id']; ?>">
                        <button class="button danger small" type="submit">Hapus Pendaftar</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
