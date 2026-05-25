<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('gallery');

$message = '';
$error = '';
$editRow = null;
$galleryImageMaxBytes = IMAGE_UPLOAD_MAX_BYTES;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_gallery_item((int) $_POST['delete_id']);
        $message = 'Item galeri berhasil dihapus.';
    } else {
        $existing = !empty($_POST['id']) ? admin_gallery_row((int) $_POST['id']) : null;
        $upload = uploaded_image_result('image_file', 'gallery', $galleryImageMaxBytes);
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($_POST['existing_image_path'] ?? ($existing['image_path'] ?? '/public/logo-sekolah.png'));
        }

        if (!$error) {
            save_gallery_item([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'image_path' => $imagePath,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Item galeri berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_gallery_row((int) $_GET['edit']);
}

$rows = admin_gallery_rows();
$pageTitle = 'Kelola Galeri';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>

<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Galeri</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Galeri' : 'Kelola Galeri'; ?></strong>
                    <small>Tambah atau ubah item galeri.</small>
                </span>
            </summary>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar CRUD galeri aktif.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label>
                    <span>Upload Gambar</span>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text($galleryImageMaxBytes)); ?></span>
                    <?php if ($editRow && !empty($editRow['image_path'])): ?>
                        <img class="admin-preview" src="<?= asset(ltrim($editRow['image_path'], '/')); ?>" alt="Preview gambar galeri">
                    <?php endif; ?>
                </label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <div class="action-bar">
                    <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Item' : 'Simpan Item'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/gallery.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Judul</th><th>Gambar</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']); ?></td>
                    <td><?= e($row['image_path']); ?></td>
                    <td class="action-cell">
                        <a class="button primary small" href="<?= base_url('admin/gallery.php?edit=' . (int) $row['id']); ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus item galeri ini?');">
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
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
