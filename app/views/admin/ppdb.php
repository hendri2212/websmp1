<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>

<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Informasi PPDB</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Informasi PPDB' : 'Kelola Informasi PPDB'; ?></strong>
                    <small>Tambah atau ubah informasi PPDB publik.</small>
                </span>
            </summary>
            <p>Kelola informasi PPDB publik seperti jalur, alur, persyaratan, catatan, dan kontak panitia. Edaran atau PDF PPDB diunggah dari menu Dokumen dengan kategori <strong>PPDB</strong>.</p>
            <p class="muted">Untuk teks pengantar, gunakan seksi <strong>pengantar</strong> dan judul <strong>halaman</strong>, <strong>kriteria</strong>, <strong>dokumen</strong>, atau <strong>kontak</strong>. Teks {tahun_ajaran} mengikuti tahun ajaran aktif.</p>
            <?php if (!db_available()): ?><div class="alert warning">Database belum tersedia untuk mengelola informasi PPDB.</div><?php endif; ?>
            <form method="post" class="form-grid">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label>
                    <span>Seksi</span>
                    <input type="text" name="section" placeholder="informasi / kriteria / catatan / persyaratan / pengantar" value="<?= e($editRow['section'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Konten</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['content'] ?? ''); ?></textarea></label>
                <div class="action-bar">
                    <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Informasi' : 'Simpan Informasi'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/ppdb.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada konten informasi PPDB.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Seksi</th><th>Judul</th><th>Urutan</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['section']); ?></td>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e((string) $row['sort_order']); ?></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/ppdb.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus informasi PPDB ini?');">
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
