<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('ppdb_content');

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_ppdb_entry((int) $_POST['delete_id']);
        $message = 'Entry PPDB berhasil dihapus.';
    } else {
        save_ppdb_entry([
            'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
            'section' => trim($_POST['section']),
            'title' => trim($_POST['title']),
            'content' => trim($_POST['content']),
            'sort_order' => (int) ($_POST['sort_order'] ?: 0),
        ]);
        $message = 'Entry PPDB berhasil disimpan.';
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_ppdb_row((int) $_GET['edit']);
}

$rows = ppdb_entries();

$pageTitle = 'Kelola PPDB';
require __DIR__ . '/../templates/admin_header.php';
?>
<<<<<<< HEAD
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>

<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Entry PPDB</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Entry PPDB' : 'Kelola PPDB'; ?></strong>
                    <small>Tambah atau ubah informasi PPDB publik.</small>
                </span>
            </summary>
            <p>Kelola informasi PPDB publik seperti jalur, alur, persyaratan, catatan, dan kontak panitia. Edaran atau PDF PPDB diunggah dari menu Dokumen dengan kategori <strong>PPDB</strong>.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar CRUD PPDB aktif.</div><?php endif; ?>
            <form method="post" class="form-grid">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label>
                    <span>Seksi</span>
                    <input type="text" name="section" placeholder="informasi / kriteria / catatan / persyaratan" value="<?= e($editRow['section'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>>
                </label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Konten</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['content'] ?? ''); ?></textarea></label>
                <div class="action-bar">
                    <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Entry' : 'Simpan Entry'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/ppdb.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
=======
<div class="admin-card">
    <h1>Kelola PPDB</h1>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar CRUD PPDB aktif.</div><?php endif; ?>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
        <label>
            <span>Seksi</span>
            <input type="text" name="section" placeholder="informasi / kriteria / catatan / persyaratan" value="<?= e($editRow['section'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>>
        </label>
        <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label class="full"><span>Konten</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['content'] ?? ''); ?></textarea></label>
        <div class="action-bar">
            <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Entry' : 'Simpan Entry'; ?></button>
            <?php if ($editRow): ?>
                <a class="button secondary" href="<?= base_url('admin/ppdb.php'); ?>">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="admin-card">
    <h2>Daftar Entry PPDB</h2>
>>>>>>> 98147e3 (upload pertama)
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data PPDB di database.</p>
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
                            <form method="post" onsubmit="return confirm('Hapus entry PPDB ini?');">
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
