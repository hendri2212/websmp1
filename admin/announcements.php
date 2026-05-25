<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('announcements');

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_announcement((int) $_POST['delete_id']);
        $message = 'Pengumuman berhasil dihapus.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $contentText = trim($_POST['content'] ?? '');
        $startsAt = trim($_POST['starts_at'] ?? '');
        $endsAt = trim($_POST['ends_at'] ?? '');

        if ($title === '' || $contentText === '') {
            $error = 'Judul dan isi pengumuman wajib diisi.';
        } else {
            save_announcement([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'title' => $title,
                'content' => $contentText,
                'link_label' => trim($_POST['link_label'] ?? '') ?: null,
                'link_url' => trim($_POST['link_url'] ?? '') ?: null,
                'starts_at' => $startsAt !== '' ? str_replace('T', ' ', $startsAt) . ':00' : null,
                'ends_at' => $endsAt !== '' ? str_replace('T', ' ', $endsAt) . ':00' : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Pengumuman berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_announcement_row((int) $_GET['edit']);
}

$rows = admin_announcement_rows();
$pageTitle = 'Kelola Pengumuman';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Pengumuman</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Pengumuman' : 'Kelola Pengumuman'; ?></strong>
                    <small>Tambah atau ubah pengumuman sekolah.</small>
                </span>
            </summary>
            <p>Buat banner pengumuman yang tampil di beranda, misalnya informasi PPDB, ujian, libur, atau rapat orang tua.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar pengumuman bisa dikelola.</div><?php endif; ?>
            <form method="post" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label class="full"><span>Isi Pengumuman</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['content'] ?? ''); ?></textarea></label>
                <label><span>Label Tombol</span><input type="text" name="link_label" placeholder="Lihat Detail" value="<?= e($editRow['link_label'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>URL Tombol</span><input type="text" name="link_url" placeholder="/ppdb atau https://..." value="<?= e($editRow['link_url'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Mulai Tayang</span><input type="datetime-local" name="starts_at" value="<?= e(!empty($editRow['starts_at']) ? date('Y-m-d\TH:i', strtotime($editRow['starts_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Akhir Tayang</span><input type="datetime-local" name="ends_at" value="<?= e(!empty($editRow['ends_at']) ? date('Y-m-d\TH:i', strtotime($editRow['ends_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Aktifkan pengumuman</span></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Pengumuman' : 'Simpan Pengumuman'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/announcements.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada pengumuman.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Status</th><th>Jadwal</th><th>Urutan</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif'; ?></td>
                        <td><?= e(($row['starts_at'] ?: 'Sekarang') . ' - ' . ($row['ends_at'] ?: 'Tanpa batas')); ?></td>
                        <td><?= e((string) $row['sort_order']); ?></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/announcements.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus pengumuman ini?');">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $row['id']; ?>">
                                <button class="button danger small" type="submit" <?= db_available() ? '' : 'disabled'; ?>>Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
