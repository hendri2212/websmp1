<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('change_password');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru belum sama.';
    } else {
        $result = update_admin_password(admin_username() ?? '', $currentPassword, $newPassword);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Ganti Password Admin';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Ganti Password Admin</h1>
    <p>Gunakan halaman ini untuk mengganti password login admin agar akun lebih aman sebelum website dipublikasikan.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?>
        <div class="alert warning">Database belum terhubung. Hubungkan MySQL terlebih dahulu agar password admin bisa diperbarui.</div>
    <?php endif; ?>
    <form method="post" class="form-grid" autocomplete="off">
        <?= csrf_field(); ?>
        <label><span>Username Aktif</span><input type="text" value="<?= e(admin_username() ?? ''); ?>" disabled></label>
        <label><span>Password Saat Ini</span><input type="password" name="current_password" required <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Password Baru</span><input type="password" name="new_password" required minlength="8" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Konfirmasi Password Baru</span><input type="password" name="confirm_password" required minlength="8" <?= db_available() ? '' : 'disabled'; ?>></label>
        <div class="action-bar">
            <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>>Simpan Password Baru</button>
            <a class="button secondary" href="<?= base_url('admin/dashboard.php'); ?>">Kembali ke Dashboard</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
