<div class="admin-card">
    <h1>Pesan Masuk</h1>
    <p>Kelola pesan yang dikirim pengunjung dari halaman kontak website sekolah.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar pesan masuk bisa dikelola.</div><?php endif; ?>
</div>

<div class="admin-card">
    <h2>Daftar Pesan</h2>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada pesan masuk.</p>
    <?php else: ?>
        <div class="message-list">
            <?php foreach ($rows as $row): ?>
                <article class="message-item">
                    <div class="message-head">
                        <div>
                            <span class="status-badge status-<?= e($row['status']); ?>"><?= e($statusLabels[$row['status']] ?? $row['status']); ?></span>
                            <h3><?= e($row['subject']); ?></h3>
                            <p class="muted"><?= e($row['name']); ?> • <?= e($row['email']); ?> • <?= e($row['phone']); ?></p>
                            <p class="muted"><?= e(date('d F Y H:i', strtotime($row['created_at']))); ?></p>
                        </div>
                        <form method="post" class="status-form">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="message_id" value="<?= (int) $row['id']; ?>">
                            <select name="status">
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?= e($value); ?>" <?= $row['status'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button secondary small" type="submit">Update</button>
                        </form>
                    </div>
                    <p><?= nl2br(e($row['message'])); ?></p>
                    <form method="post" onsubmit="return confirm('Hapus pesan ini?');">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $row['id']; ?>">
                        <button class="button danger small" type="submit">Hapus Pesan</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
