<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('extracurriculars');

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Wajib', 'Olahraga', 'Seni', 'Akademik', 'Organisasi', 'Kesehatan', 'Keagamaan', 'Teknologi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_extracurricular((int) $_POST['delete_id']);
        $message = 'Data ekstrakurikuler berhasil dihapus.';
    } else {
        $existing = !empty($_POST['id']) ? admin_extracurricular_row((int) $_POST['id']) : null;
        $upload = uploaded_image_result('image_file', 'extracurriculars');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($_POST['existing_image_path'] ?? ($existing['image_path'] ?? '/public/logo-sekolah.png'));
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($name === '' || $category === '' || $description === '')) {
            $error = 'Nama ekskul, kategori, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_extracurricular([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'name' => $name,
                'category' => $category,
                'coach' => trim($_POST['coach'] ?? '') ?: null,
                'schedule_text' => trim($_POST['schedule_text'] ?? '') ?: null,
                'location' => trim($_POST['location'] ?? '') ?: null,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Data ekstrakurikuler berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_extracurricular_row((int) $_GET['edit']);
}

$rows = admin_extracurricular_rows();
$pageTitle = 'Kelola Ekstrakurikuler';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Ekstrakurikuler</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Ekstrakurikuler' : 'Kelola Ekstrakurikuler'; ?></strong>
                    <small>Tambah atau ubah kegiatan ekstrakurikuler.</small>
                </span>
            </summary>
            <p>Tambahkan kegiatan ekstrakurikuler beserta pembina, jadwal, lokasi, foto, dan deskripsi kegiatan.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar data ekstrakurikuler bisa dikelola.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label><span>Nama Ekstrakurikuler</span><input type="text" name="name" value="<?= e($editRow['name'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="extracurricular-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="extracurricular-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Pembina</span><input type="text" name="coach" value="<?= e($editRow['coach'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Jadwal</span><input type="text" name="schedule_text" value="<?= e($editRow['schedule_text'] ?? ''); ?>" placeholder="Contoh: Rabu, 15.00-16.30" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Lokasi</span><input type="text" name="location" value="<?= e($editRow['location'] ?? ''); ?>" placeholder="Lapangan / Aula / Lab" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label>
                    <span>Foto Kegiatan</span>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <?php if ($editRow && !empty($editRow['image_path'])): ?>
                        <img class="admin-preview" src="<?= asset(ltrim($editRow['image_path'], '/')); ?>" alt="Preview ekstrakurikuler">
                    <?php endif; ?>
                </label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Ekskul' : 'Simpan Ekskul'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/extracurriculars.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data ekstrakurikuler.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nama</th><th>Kategori</th><th>Pembina</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['coach'] ?? '-'); ?></td>
                        <td><?= e($row['schedule_text'] ?? '-'); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/extracurriculars.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus ekstrakurikuler ini?');">
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
