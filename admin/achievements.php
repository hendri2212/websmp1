<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('achievements');

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Akademik', 'Non-Akademik', 'Olahraga', 'Seni', 'Karya Ilmiah', 'Sekolah'];
$defaultLevels = ['Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional'];
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_achievement((int) $_POST['delete_id']);
        $message = 'Data prestasi berhasil dihapus.';
    } else {
        $existing = !empty($_POST['id']) ? admin_achievement_row((int) $_POST['id']) : null;
        $upload = uploaded_image_result('image_file', 'achievements');
        $imagePath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($imagePath === null) {
            $imagePath = trim($_POST['existing_image_path'] ?? ($existing['image_path'] ?? '/public/logo-sekolah.png'));
        }

        $title = trim($_POST['title'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $achieverName = trim($_POST['achiever_name'] ?? '');
        $achievementDate = trim($_POST['achievement_date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($title === '' || $level === '' || $category === '' || $achieverName === '' || $achievementDate === '' || $description === '')) {
            $error = 'Judul, tingkat, kategori, peraih, tanggal, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_achievement([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'title' => $title,
                'level' => $level,
                'category' => $category,
                'achiever_name' => $achieverName,
                'achievement_date' => $achievementDate,
                'description' => $description,
                'image_path' => $imagePath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Data prestasi berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_achievement_row((int) $_GET['edit']);
}

$rows = admin_achievement_rows();
$pageTitle = 'Kelola Prestasi';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Prestasi</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Prestasi' : 'Kelola Prestasi'; ?></strong>
                    <small>Tambah atau ubah prestasi sekolah.</small>
                </span>
            </summary>
            <p>Tambahkan prestasi akademik, non-akademik, lomba, penghargaan guru, atau capaian sekolah yang akan tampil di halaman publik.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar data prestasi bisa dikelola.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_image_path" value="<?= e($editRow['image_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label class="full"><span>Judul Prestasi</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="achievement-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="achievement-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    <span>Tingkat</span>
                    <input type="text" name="level" list="achievement-levels" value="<?= e($editRow['level'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="achievement-levels">
                        <?php foreach ($defaultLevels as $level): ?>
                            <option value="<?= e($level); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Peraih Prestasi</span><input type="text" name="achiever_name" value="<?= e($editRow['achiever_name'] ?? ''); ?>" placeholder="Nama siswa, tim, guru, atau sekolah" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Tanggal Prestasi</span><input type="date" name="achievement_date" value="<?= e($editRow['achievement_date'] ?? date('Y-m-d')); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($editRow['academic_year'] ?? $currentAcademicYear); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label>
                    <span>Foto Prestasi</span>
                    <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <?php if ($editRow && !empty($editRow['image_path'])): ?>
                        <img class="admin-preview" src="<?= asset(ltrim($editRow['image_path'], '/')); ?>" alt="Preview prestasi">
                    <?php endif; ?>
                </label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Prestasi' : 'Simpan Prestasi'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/achievements.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data prestasi.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Peraih</th><th>Kategori</th><th>Tahun Ajaran</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e($row['achiever_name']); ?></td>
                        <td><?= e($row['category']); ?> - <?= e($row['level']); ?></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><?= date('d/m/Y', strtotime($row['achievement_date'])); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/achievements.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus prestasi ini?');">
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
