<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('documents');

$message = '';
$error = '';
$editRow = null;
$defaultCategories = ['Kalender Akademik', 'Formulir', 'Surat Edaran', 'Tata Tertib', 'PPDB', 'Lainnya'];
$currentAcademicYear = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && db_available()) {
    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (isset($_POST['delete_id'])) {
        delete_school_document((int) $_POST['delete_id']);
        $message = 'Dokumen berhasil dihapus.';
    } else {
        $existing = !empty($_POST['id']) ? admin_document_row((int) $_POST['id']) : null;
        $upload = uploaded_pdf_result('document_file', 'documents');
        $filePath = $upload['path'];
        $originalFilename = $upload['original_filename'];
        $fileSize = $upload['file_size'];

        if ($upload['error']) {
            $error = $upload['error'];
        } elseif ($filePath === null && !$existing) {
            $error = 'File PDF wajib diunggah untuk dokumen baru.';
        } elseif ($filePath === null && $existing) {
            $filePath = $existing['file_path'];
            $originalFilename = $existing['original_filename'];
            $fileSize = (int) $existing['file_size'];
        }

        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$error && ($title === '' || $category === '' || $description === '')) {
            $error = 'Judul, kategori, dan deskripsi wajib diisi.';
        }

        if (!$error) {
            save_school_document([
                'id' => $_POST['id'] !== '' ? (int) $_POST['id'] : null,
                'title' => $title,
                'category' => $category,
                'description' => $description,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'academic_year' => trim($_POST['academic_year'] ?? $currentAcademicYear) ?: $currentAcademicYear,
                'sort_order' => (int) ($_POST['sort_order'] ?: 0),
            ]);
            $message = 'Dokumen berhasil disimpan.';
        }
    }
}

if (isset($_GET['edit']) && db_available()) {
    $editRow = admin_document_row((int) $_GET['edit']);
}

$rows = admin_document_rows();
$pageTitle = 'Kelola Dokumen';
require __DIR__ . '/../templates/admin_header.php';
?>
<<<<<<< HEAD
<?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>


<div class="admin-card admin-list-card">
    <div class="admin-list-head">
        <h2>Daftar Dokumen</h2>
        <details class="admin-list-add admin-form-toggle" <?= ($editRow || $error || !db_available()) ? 'open' : ''; ?>>
            <summary>
                <span>
                    <strong><?= $editRow ? 'Edit Dokumen' : 'Kelola Dokumen'; ?></strong>
                    <small>Tambah atau ubah dokumen sekolah.</small>
                </span>
            </summary>
            <p>Unggah PDF seperti kalender akademik, formulir, surat edaran, tata tertib, dan dokumen resmi sekolah.</p>
            <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar dokumen bisa dikelola.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form-grid two-col">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
                <label class="full"><span>Judul Dokumen</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
                <label>
                    <span>Kategori</span>
                    <input type="text" name="category" list="document-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
                    <datalist id="document-categories">
                        <?php foreach ($defaultCategories as $category): ?>
                            <option value="<?= e($category); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($editRow['academic_year'] ?? $currentAcademicYear); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
                <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
                <label>
                    <span>File PDF</span>
                    <input type="file" name="document_file" accept=".pdf,application/pdf" <?= db_available() ? '' : 'disabled'; ?> <?= $editRow ? '' : 'required'; ?>>
                    <?php if ($editRow && !empty($editRow['original_filename'])): ?>
                        <span class="muted">File saat ini: <?= e($editRow['original_filename']); ?></span>
                    <?php endif; ?>
                </label>
                <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
                <div class="action-bar full">
                    <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Dokumen' : 'Simpan Dokumen'; ?></button>
                    <?php if ($editRow): ?>
                        <a class="button secondary" href="<?= base_url('admin/documents.php'); ?>">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </details>
    </div>
=======
<div class="admin-card">
    <h1>Kelola Dokumen</h1>
    <p>Unggah PDF seperti kalender akademik, formulir, surat edaran, tata tertib, dan dokumen resmi sekolah.</p>
    <?php if ($message): ?><div class="alert success"><?= e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error); ?></div><?php endif; ?>
    <?php if (!db_available()): ?><div class="alert warning">Sambungkan MySQL agar dokumen bisa dikelola.</div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-grid two-col">
        <?= csrf_field(); ?>
        <input type="hidden" name="id" value="<?= e($editRow['id'] ?? ''); ?>">
        <label class="full"><span>Judul Dokumen</span><input type="text" name="title" value="<?= e($editRow['title'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required></label>
        <label>
            <span>Kategori</span>
            <input type="text" name="category" list="document-categories" value="<?= e($editRow['category'] ?? ''); ?>" <?= db_available() ? '' : 'disabled'; ?> required>
            <datalist id="document-categories">
                <?php foreach ($defaultCategories as $category): ?>
                    <option value="<?= e($category); ?>">
                <?php endforeach; ?>
            </datalist>
        </label>
        <label><span>Tahun Ajaran</span><input type="text" name="academic_year" value="<?= e($editRow['academic_year'] ?? $currentAcademicYear); ?>" placeholder="2025/2026" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label><span>Urutan</span><input type="number" name="sort_order" value="<?= e((string) ($editRow['sort_order'] ?? 0)); ?>" <?= db_available() ? '' : 'disabled'; ?>></label>
        <label class="full"><span>Deskripsi</span><textarea name="description" <?= db_available() ? '' : 'disabled'; ?> required><?= e($editRow['description'] ?? ''); ?></textarea></label>
        <label>
            <span>File PDF</span>
            <input type="file" name="document_file" accept=".pdf,application/pdf" <?= db_available() ? '' : 'disabled'; ?> <?= $editRow ? '' : 'required'; ?>>
            <?php if ($editRow && !empty($editRow['original_filename'])): ?>
                <span class="muted">File saat ini: <?= e($editRow['original_filename']); ?></span>
            <?php endif; ?>
        </label>
        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= (int) ($editRow['is_active'] ?? 1) === 1 ? 'checked' : ''; ?> <?= db_available() ? '' : 'disabled'; ?>> <span>Publish ke halaman publik</span></label>
        <div class="action-bar full">
            <button class="button primary blue" type="submit" <?= db_available() ? '' : 'disabled'; ?>><?= $editRow ? 'Update Dokumen' : 'Simpan Dokumen'; ?></button>
            <?php if ($editRow): ?>
                <a class="button secondary" href="<?= base_url('admin/documents.php'); ?>">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>Daftar Dokumen</h2>
>>>>>>> 98147e3 (upload pertama)
    <?php if (!$rows): ?>
        <p class="muted">Belum ada dokumen.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Judul</th><th>Kategori</th><th>Tahun Ajaran</th><th>File</th><th>Unduhan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['title']); ?></td>
                        <td><?= e($row['category']); ?></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><?= e($row['original_filename']); ?></td>
                        <td><?= (int) $row['download_count']; ?></td>
                        <td><span class="status-badge <?= e(publication_status_class($row['is_active'])); ?>"><?= e(publication_status_label($row['is_active'])); ?></span></td>
                        <td class="action-cell">
                            <?php if ((int) $row['is_active'] === 1): ?>
                                <a class="button secondary small" href="<?= base_url('dokumen/unduh?id=' . (int) $row['id']); ?>">Unduh</a>
                            <?php endif; ?>
                            <a class="button primary small" href="<?= base_url('admin/documents.php?edit=' . (int) $row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus dokumen ini?');">
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
