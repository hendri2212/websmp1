<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('staff');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('staff', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_staff_member(database_id($_POST['delete_id'])), 'Data guru/staf berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $existing = !empty($_POST['id']) ? admin_staff_row(database_id($_POST['id'])) : null;
        $upload = uploaded_image_result('photo_file', 'staff');
        $photoPath = $upload['path'];
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($photoPath === null) {
            $photoPath = trim($existing['photo_path'] ?? '/public/logo-sekolah.png');
        }

        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$error && ($name === '' || $position === '' || $subject === '')) {
            $error = 'Nama, jabatan, dan mapel/bidang wajib diisi.';
        } elseif (!$error && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email belum valid.';
        }

        if (!$error) {
            save_staff_member([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'name' => $name,
                'position' => $position,
                'subject' => $subject,
                'bio' => $bio,
                'phone' => trim($_POST['phone'] ?? '') ?: null,
                'email' => $email ?: null,
                'photo_path' => $photoPath,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            commit_uploaded_files();
            $message = 'Data guru/staf berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_staff_row(database_id($_GET['edit']));
}

$rows = admin_staff_rows();
$pageTitle = 'Kelola Guru & Staf';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Guru & Staf</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Guru & Staf' : 'Kelola Guru & Staf'; ?></strong>
                    <small>Tambah atau ubah data pendidik dan tenaga kependidikan.</small>
                </span>
            </summary>
            <p>Tambahkan data pendidik dan tenaga kependidikan yang akan tampil di halaman publik Guru & Staf.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar data guru dan staf bisa dikelola.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <input type="hidden" name="existing_photo_path" value="<?= e($editRow['photo_path'] ?? '/public/logo-sekolah.png'); ?>">
                <label><span>Nama</span><input type="text" name="name" value="<?= e($editRow['name'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Jabatan</span><input type="text" name="position" value="<?= e($editRow['position'] ?? ''); ?>" placeholder="Guru / Kepala Sekolah / Staf TU" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Mapel/Bidang</span><input type="text" name="subject" value="<?= e($editRow['subject'] ?? ''); ?>" placeholder="Matematika / Administrasi / BK" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Email</span><input type="email" name="email" value="<?= e($editRow['email'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Telepon/WhatsApp</span><input type="text" name="phone" value="<?= e($editRow['phone'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label>
                    <span>Foto</span>
                    <input type="file" name="photo_file" accept=".jpg,.jpeg,.png,.webp" <?= db_available() ? '' : 'disabled'; ?>>
                    <span class="muted"><?= e(image_upload_help_text()); ?></span>
                    <?php if ($editRow && !empty($editRow['photo_path'])): ?>
                        <img class="admin-preview" src="<?= e(asset(ltrim($editRow['photo_path'], '/'))); ?>" alt="Preview foto guru/staf">
                    <?php endif; ?>
                </label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Aktif tampil di publik</span></label>
                <label class="full"><span>Profil Singkat</span><textarea name="bio" <?= db_available() ? '' : 'disabled'; ?>><?= e($editRow['bio'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Data' : 'Simpan Data'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= e(base_url('admin/staff.php')); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada data guru dan staf.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nama</th><th>Jabatan</th><th>Bidang</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']); ?></td>
                        <td><?= e($row['position']); ?></td>
                        <td><?= e($row['subject']); ?></td>
                        <td><?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif'; ?></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= e(base_url('admin/staff.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus data ini?');">
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
