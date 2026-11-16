<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar FAQ</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit FAQ' : 'Kelola FAQ'; ?></strong>
                    <small>Tambah atau ubah pertanyaan umum.</small>
                </span>
            </summary>
            <p>Tambahkan pertanyaan umum yang sering ditanyakan orang tua, calon siswa, dan masyarakat.</p>
            <?php if (!db_available()): ?><div class="alert warning">Layanan sementara tidak tersedia. Silakan coba lagi.</div><?php endif; ?>
            <form method="post" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label class="full"><span>Pertanyaan</span><input type="text" name="question" value="<?= e($editRow['question'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="faq-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="faq-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label class="full"><span>Jawaban</span><textarea name="answer" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['answer'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update FAQ' : 'Simpan FAQ'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= e(base_url('admin/faqs.php')); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada FAQ.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Pertanyaan</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['question']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= e(base_url('admin/faqs.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus FAQ ini?');">
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
