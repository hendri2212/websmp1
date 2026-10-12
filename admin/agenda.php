<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('agenda');
require_valid_admin_request_ids();

$message = '';
$error = '';
$editRow = null;
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($validationError = input_validation_error('agenda', $_POST, $_FILES)) !== '') {
        $error = $validationError;
    } elseif (isset($_POST['delete_id'])) {
        $result = admin_delete_feedback(static fn()=>delete_agenda_item(database_id($_POST['delete_id'])), 'Agenda berhasil dihapus.');
        $message = $result['success'] ? $result['message'] : '';
        $error = $result['success'] ? '' : $result['message'];
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startsAt = trim($_POST['starts_at'] ?? '');
        $endsAt = trim($_POST['ends_at'] ?? '');

        if ($title === '' || $category === '' || $location === '' || $description === '' || $startsAt === '') {
            $error = 'Judul, kategori, lokasi, deskripsi, dan tanggal mulai wajib diisi.';
        } else {
            save_agenda_item([
                'id' => $_POST['id'] !== '' ? database_id($_POST['id']) : null,
                'title' => $title,
                'category' => $category,
                'location' => $location,
                'description' => $description,
                'starts_at' => input_datetime_value($startsAt),
                'ends_at' => $endsAt !== '' ? input_datetime_value($endsAt) : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Agenda berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_agenda_row(database_id($_GET['edit']));
}

$rows = admin_agenda_rows();
$pageTitle = 'Kelola Agenda';
require __DIR__ . '/../templates/admin_header.php';
?>
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Agenda</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Agenda' : 'Kelola Agenda'; ?></strong>
                    <small>Tambah atau ubah agenda sekolah.</small>
                </span>
            </summary>
            <p>Tambahkan agenda kegiatan sekolah seperti ujian, rapat, lomba, kegiatan kesiswaan, dan kalender akademik.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar agenda bisa dikelola.</div><?php endif; ?>
            <form method="post" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label class="full"><span>Judul Agenda</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Kategori</span><input type="text" name="category" placeholder="Akademik / Kesiswaan / Rapat" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Lokasi</span><input type="text" name="location" value="<?= e($editRow['location'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Mulai</span><input type="datetime-local" name="starts_at" value="<?= e(!empty($editRow['starts_at']) ? date('Y-m-d\TH:i', strtotime($editRow['starts_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label><span>Selesai</span><input type="datetime-local" name="ends_at" value="<?= e(!empty($editRow['ends_at']) ? date('Y-m-d\TH:i', strtotime($editRow['ends_at'])) : ''); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($editRow['academic_year'] ?? $currentAcademicYear); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Agenda' : 'Simpan Agenda'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= e(base_url('admin/agenda.php')); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
    <?php if (!$rows): ?>
        <p class="muted">Belum ada agenda.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Kategori</th><th>Tahun Ajaran</th><th>Mulai</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><?= e(date('d F Y H:i', strtotime($row['starts_at']))); ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <a class="button primary small" href="<?= e(base_url('admin/agenda.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus agenda ini?');">
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
