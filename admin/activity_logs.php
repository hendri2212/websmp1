<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('activity_logs');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'module' => trim($_GET['module'] ?? ''),
    'action' => trim($_GET['action'] ?? ''),
];
$logs = admin_activity_logs($filters);
$modules = admin_activity_log_options('module');
$actions = admin_activity_log_options('action');

$pageTitle = 'Log Aktivitas Admin';
require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Log Aktivitas Admin</h1>
    <p>Catatan audit untuk login, logout, perubahan konten, penghapusan data, backup database, export PPDB, dan perubahan password.</p>

    <form method="get" class="filter-bar">
        <label>
            <span>Cari</span>
            <input type="search" name="q" value="<?= e($filters['q']); ?>" placeholder="username, deskripsi, modul">
        </label>
        <label>
            <span>Modul</span>
            <select name="module">
                <option value="">Semua modul</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module); ?>" <?= $filters['module'] === $module ? 'selected' : ''; ?>><?= e($module); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Aksi</span>
            <select name="action">
                <option value="">Semua aksi</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= e($action); ?>" <?= $filters['action'] === $action ? 'selected' : ''; ?>><?= e($action); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="filter-actions">
            <button class="button primary blue" type="submit">Filter</button>
            <a class="button secondary" href="<?= base_url('admin/activity_logs.php'); ?>">Reset</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>Riwayat Aktivitas</h2>
    <?php if (!$logs): ?>
        <p class="muted">Belum ada aktivitas yang tercatat.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Modul</th>
                        <th>Aksi</th>
                        <th>Keterangan</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e(date('d M Y H:i', strtotime($log['created_at']))); ?></td>
                            <td>
                                <strong><?= e($log['username'] ?? '-'); ?></strong><br>
                                <span class="muted"><?= e(admin_role_label($log['role'] ?? 'editor')); ?></span>
                            </td>
                            <td><?= e($log['module']); ?></td>
                            <td><span class="status-badge status-diverifikasi"><?= e($log['action']); ?></span></td>
                            <td>
                                <?= e($log['description']); ?>
                                <?php if (!empty($log['entity_type'])): ?>
                                    <br><span class="muted"><?= e($log['entity_type']); ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : ''; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($log['ip_address'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
