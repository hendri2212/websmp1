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
