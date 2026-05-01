<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('news');

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_news((int) $_POST['delete_id']);
        $message = 'Berita berhasil dihapus.';
    } else {
        $existing = !empty($_POST['id']) ? admin_news_row((int) $_POST['id']) : null;
        $upload = uploaded_image_result('image_file', 'news');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($_POST['existing_image_path'] ?? ($existing['image_path'] ?? '/public/logo-sekolah.png'));
        }

        if (!$error) {
            save_news([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'slug' => trim($_POST['slug']),
                'title' => trim($_POST['title']),
                'category' => trim($_POST['category']),
                'excerpt' => trim($_POST['excerpt']),
                'content' => trim($_POST['content']),
                'image_path' => $imagePath,
                'published_at' => trim($_POST['published_at']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            $message = 'Berita berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_news_row((int) $_GET['edit']);
}

$rows = admin_news_rows();
$pageTitle = 'Kelola Berita';
require __DIR__ . '/../templates/admin_header.php';
?>
<<<<<<< HEAD
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>

<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Berita</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Berita' : 'Kelola Berita'; ?></strong>
                    <small>Tambah atau ubah berita sekolah.</small>
                </span>
            </summary>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL dan import schema agar CRUD berita aktif.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label><span>Slug</span><input type="text" name="slug" placeholder="judul-berita" value="<?= e($editRow['slug'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Kategori</span><input type="text" name="category" placeholder="Akademik" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Tanggal Publikasi</span><input type="datetime-local" name="published_at" value="<?= e(isset($editRow['published_at']) ? date('Y-m-d\TH:i', strtotime($editRow['published_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label>
                    <span>Upload Gambar</span>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <?php if ($editRow && !empty($editRow['image_path'])): ?>
                        <img class="admin-preview" src="<?= asset(ltrim($editRow['image_path'], '/')); ?>" alt="Preview gambar berita">
                    <?php endif; ?>
                </label>
                <label class="full"><span>Ringkasan</span><textarea name="excerpt" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['excerpt'] ?? ''); ?></textarea></label>
                <label class="full"><span>Isi Berita</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['content'] ?? ''); ?></textarea></label>
                <div class="action-bar">
                    <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Berita' : 'Simpan Berita'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/news.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
=======
<div class="admin-card">
    <h1>Kelola Berita</h1>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL dan import schema agar CRUD berita aktif.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
        <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
        <label><span>Slug</span><input type="text" name="slug" placeholder="judul-berita" value="<?= e($editRow['slug'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Kategori</span><input type="text" name="category" placeholder="Akademik" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label class="full"><span>Judul</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Tanggal Publikasi</span><input type="datetime-local" name="published_at" value="<?= e(isset($editRow['published_at']) ? date('Y-m-d\TH:i', strtotime($editRow['published_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
        <label>
            <span>Upload Gambar</span>
            <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
            <span class="muted"><?= e(image_upload_help_text()); ?></span>
            <?php if ($editRow && !empty($editRow['image_path'])): ?>
                <img class="admin-preview" src="<?= asset(ltrim($editRow['image_path'], '/')); ?>" alt="Preview gambar berita">
            <?php endif; ?>
        </label>
        <label class="full"><span>Ringkasan</span><textarea name="excerpt" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['excerpt'] ?? ''); ?></textarea></label>
        <label class="full"><span>Isi Berita</span><textarea name="content" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['content'] ?? ''); ?></textarea></label>
        <div class="action-bar">
            <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Berita' : 'Simpan Berita'; ?></button>
            <?php if ($editRow): ?>
                <a class="button secondary" href="<?= base_url('admin/news.php'); ?>">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="admin-card">
    <h2>Daftar Berita</h2>
>>>>>>> 98147e3 (upload pertama)
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data berita di database.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['published_at']); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'] ?? 1)); ?>"><?= e(publication_status_label($row['is_active'] ?? 1)); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/news.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus berita ini?');">
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
