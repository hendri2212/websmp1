<?php
require __DIR__ . '/../includes/bootstrap.php';

if (admin_is_logged_in()) {
    redirect_to('admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf_token'] ?? null) || !csrf_token_is_valid($_POST['csrf_token'])) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (admin_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect_to('admin/dashboard.php');
    } else {
        $error = 'Username atau password tidak sesuai.';
    }
}

$pageTitle = 'Login Admin';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card login-card">
    <h1>Login Admin</h1>
    <p>Gunakan akun admin untuk mengelola profil sekolah, berita, galeri, dan informasi PPDB.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <label><span>Username</span><input type="text" name="username" required></label>
        <label><span>Password</span><input type="password" name="password" required></label>
        <button class="button primary" type="submit">Masuk</button>
    </form>
    <p class="muted">Login admin hanya memakai akun yang tersimpan di database. Segera ganti password awal setelah import schema.</p>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
