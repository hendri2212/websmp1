<div class="admin-card">
    <h1>Ganti Password Admin</h1>
    <p>Gunakan halaman ini untuk mengganti password login admin agar akun lebih aman sebelum website dipublikasikan.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?>
        <div class="alert warning">Kata sandi belum dapat diperbarui. Silakan coba lagi.</div>
    <?php endif; ?>
    <form method="post" class="form-grid" autocomplete="off">
        <?= csrf_field(); ?>
        <label><span>Username Aktif</span><input type="text" value="<?= e(admin_username() ?? ''); ?>" disabled></label>
        <label><span>Password Saat Ini</span><input type="password" name="current_password" required <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Password Baru</span><input type="password" name="new_password" required minlength="8" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Konfirmasi Password Baru</span><input type="password" name="confirm_password" required minlength="8" <?= db_available() ? '' : 'disabled'; ?>></label>
        <div class="action-bar">
            <button class="button primary" type="submit" <?= db_available() ? '' : 'disabled'; ?>>Simpan Password Baru</button>
            <a class="button secondary" href="<?= e(base_url('admin/dashboard.php')); ?>">Kembali ke Dashboard</a>
        </div>
    </form>
</div>
