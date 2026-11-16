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
            <?php if (!db_available()): ?><div class="alert warning">Layanan sementara tidak tersedia. Silakan coba lagi.</div><?php endif; ?>
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
                        <a class="button secondary" href="<?= e(base_url('admin/announcements.php')); ?>">Batal Edit</a>
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
                            <a class="button primary small" href="<?= e(base_url('admin/announcements.php?edit=' . (int) $row['id'])); ?>">Edit</a>
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
