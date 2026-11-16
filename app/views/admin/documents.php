<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Dokumen</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Dokumen' : 'Kelola Dokumen'; ?></strong>
                    <small>Tambah atau ubah dokumen sekolah.</small>
                </span>
            </summary>
            <p>Unggah PDF seperti kalender akademik, formulir, surat edaran, tata tertib, dan dokumen resmi sekolah.</p>
            <?php if (!db_available()): ?><div class="alert warning">Layanan sementara tidak tersedia. Silakan coba lagi.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label class="full"><span>Judul Dokumen</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="document-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="document-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($editRow['academic_year'] ?? $currentAcademicYear); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <label>
                    <span>File PDF</span>
                    <input type="file" name="document_file" accept=".pdf,application/pdf" <?= db_available() ? '' : 'disabled'; ?> <?= $editRow ? '' : 'required'; ?>>
                    <?php if ($editRow && !empty($editRow['original_filename'])): ?>
                        <span class="muted">File saat ini: <?= e($editRow['original_filename']); ?></span>
                    <?php endif; ?>
                </label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Dokumen' : 'Simpan Dokumen'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= e(base_url('admin/documents.php')); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada dokumen.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Kategori</th><th>Tahun Ajaran</th><th>File</th><th>Unduhan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><?= e($row['original_filename']); ?></td>
                        <td><?= (int) $row['download_count']; ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <?php if ((int) $row['is_active'] === 1): ?>
                                <a class="button secondary small" href="<?= e(base_url('dokumen/unduh?id=' . (int) $row['id'])); ?>">Unduh</a>
                            <?php endif; ?>
                            <a class="button primary small" href="<?= e(base_url('admin/documents.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus dokumen ini?');">
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
