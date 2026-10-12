<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('facilities');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Ruang Belajar', 'Laboratorium', 'Perpustakaan', 'Olahraga', 'Kesehatan', 'Ibadah', 'Administrasi', 'Pendukung'];
$defaultConditions = ['Baik', 'Sangat Baik', 'Perlu Perawatan', 'Dalam Perbaikan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('facilities', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_facility(database_id($_POST['delete_id'])), 'Data fasilitas berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_facility_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('image_file', 'facilities');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($existing['image_path'] ?? '/public/logo-sekolah.png');
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $conditionStatus = trim($_POST['condition_status'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($name === '' || $category === '' || $conditionStatus === '' || $description === '')) {
            $error = 'Nama fasilitas, kategori, kondisi, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_facility([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'name' => $name,
                'category' => $category,
                'location' => trim($_POST['location'] ?? '') ?: null,
                'capacity' => trim($_POST['capacity'] ?? '') ?: null,
                'condition_status' => $conditionStatus,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data fasilitas berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_facility_row(database_id($_GET['edit']));
}

$rows = admin_facility_rows();
$pageTitle = 'Kelola Fasilitas';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Fasilitas</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Fasilitas' : 'Kelola Fasilitas'; ?></strong>
                    <small>Tambah atau ubah fasilitas sekolah.</small>
                </span>
            </summary>
            <p>Tambahkan fasilitas sekolah seperti ruang kelas, laboratorium, perpustakaan, lapangan, UKS, musala, dan sarana pendukung lainnya. Lengkapi foto agar pengunjung dapat melihat kondisi fasilitas secara visual.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar data fasilitas bisa dikelola.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label><span>Nama Fasilitas</span><input type="text" name="name" value="<?= e($editRow['name'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="facility-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="facility-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Lokasi</span><input type="text" name="location" value="<?= e($editRow['location'] ?? ''); ?>" placeholder="Contoh: Gedung A lantai 1" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Kapasitas</span><input type="text" name="capacity" value="<?= e($editRow['capacity'] ?? ''); ?>" placeholder="Contoh: 32 siswa / 120 orang" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label>
                    <span>Kondisi</span>
                    <input type="text" name="condition_status" list="facility-conditions" value="<?= e($editRow['condition_status'] ?? 'Baik'); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="facility-conditions">
                        <?php foreach ($defaultConditions as $condition): ?>
                            <option value="<?= e($condition); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label>
                    <span>Foto Fasilitas</span>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <?php if ($editRow && !empty($editRow['image_path'])): ?>
                        <img class="admin-preview" src="<?= e(asset(ltrim($editRow['image_path'], '/'))); ?>" alt="Preview fasilitas">
                    <?php endif; ?>
                </label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Fasilitas' : 'Simpan Fasilitas'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= e(base_url('admin/facilities.php')); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data fasilitas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Foto</th><th>Nama</th><th>Kategori</th><th>Lokasi</th><th>Kondisi</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $hasPhoto = !is_placeholder_image($row['image_path'] ?? null); ?>
                    <tr>
                        <td>
                            <?php if ($hasPhoto): ?>
                                <img class="table-thumb" src="<?= e(image_asset_path($row['image_path'])); ?>" alt="<?= e($row['name']); ?>" draggable="false">
                            <?php else: ?>
                                <span class="status-badge status-diproses">Perlu Foto</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['name']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['location'] ?? '-'); ?></td>
                        <td><?= e($row['condition_status']); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= e(base_url('admin/facilities.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus fasilitas ini?');">
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
