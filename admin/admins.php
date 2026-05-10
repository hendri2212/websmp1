<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('admins');

$message = '';
$error = '';
$editRow = null;
$roleOptions = admin_role_options();
$roleDescriptions = admin_role_descriptions();
$rolePermissions = admin_permissions();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        $result = delete_admin_user((int) $_POST['delete_id']);
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $role === '') {
            $error = 'Username dan role wajib diisi.';
        } elseif (empty($_POST['id']) && $password === '') {
            $error = 'Password wajib diisi untuk akun baru.';
        }

        if (!$error) {
            $result = save_admin_user([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'username' => $username,
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => $role,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'password' => $password,
            ]);
            $message = $result['success'] ? $result['message'] : '';
            $error = $result['success'] ? '' : $result['message'];
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_user((int) $_GET['edit']);
}

$rows = admin_users();
$pageTitle = 'Kelola Akun Admin';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Kelola Akun Admin</h1>
    <p>Buat akun operator sesuai tugasnya. Superadmin memiliki akses penuh, editor mengelola konten umum, dan operator PPDB fokus pada informasi PPDB.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar akun admin bisa dikelola.</div><?php endif; ?>
    <form method="post" class="form-grid two-col" autocomplete="off">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
        <label><span>Username</span><input type="text" name="username" value="<?= e($editRow['username'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
        <label><span>Nama Lengkap</span><input type="text" name="full_name" value="<?= e($editRow['full_name'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label>
            <span>Role</span>
            <select name="role" <?= db_available() ? '' : 'disabled'; ?> required>
                <?php foreach ($roleOptions as $value => $label): ?>
                    <option value="<?= e($value); ?>" <?= ($editRow['role'] ?? 'editor') === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span><?= $editRow ? 'Password Baru (opsional)' : 'Password'; ?></span><input type="password" name="password" minlength="8" <?= db_available() ? '' : 'disabled'; ?> <?= $editRow ? '' : 'required'; ?>></label>
        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Akun aktif</span></label>
        <div class="action-bar full">
            <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Akun' : 'Simpan Akun'; ?></button>
            <?php if ($editRow): ?>
                <a class="button secondary" href="<?= base_url('admin/admins.php'); ?>">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>Ringkasan Hak Akses Role</h2>
    <p class="muted">Gunakan role paling kecil yang cukup untuk tugas operator. Fitur sensitif hanya tersedia untuk superadmin.</p>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Role</th><th>Keterangan</th><th>Akses Utama</th></tr></thead>
            <tbody>
            <?php foreach ($roleOptions as $role => $label): ?>
                <?php
                $visiblePermissions = array_values(array_filter($rolePermissions[$role] ?? [], static fn(string $permission): bool => !in_array($permission, ['dashboard'], true)));
                ?>
                <tr>
                    <td><strong><?= e($label); ?></strong></td>
                    <td><?= e($roleDescriptions[$role] ?? '-'); ?></td>
                    <td>
                        <div class="access-chip-list">
                            <?php foreach ($visiblePermissions as $permission): ?>
                                <span class="status-badge <?= in_array($permission, admin_sensitive_permissions(), true) ? 'status-ditolak' : 'status-selesai'; ?>"><?= e(admin_role_permission_label($role, $permission)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <h2>Daftar Akun Admin</h2>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada akun admin.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['username']); ?></td>
                        <td><?= e($row['full_name'] ?? '-'); ?></td>
                        <td><?= e(admin_role_label($row['role'])); ?></td>
                        <td><?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif'; ?></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= base_url('admin/admins.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus akun ini?');">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $row['id']; ?>">
                                <button class="button danger small" type="submit" <?= ((int) ($_SESSION['admin_id'] ?? 0) === (int) $row['id']) ? 'disabled' : ''; ?>>Hapus</button>
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
