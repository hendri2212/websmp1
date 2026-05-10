<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin_permission('production_check');

$pageTitle = 'Cek Kesiapan Production';
$appConfig = app_config();
$content = site_content();
$dbConfig = require __DIR__ . '/../config/database.php';
$rootPath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';
$htaccessPath = $rootPath . DIRECTORY_SEPARATOR . '.htaccess';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) === '443');
$uploadMax = ini_get('upload_max_filesize') ?: '-';
$postMax = ini_get('post_max_size') ?: '-';
$memoryLimit = ini_get('memory_limit') ?: '-';

function production_check_status(string $status): string
{
    return [
        'ok' => 'status-diterima',
        'warning' => 'status-diproses',
        'error' => 'status-ditolak',
    ][$status] ?? 'status-baru';
}

function production_check_label(string $status): string
{
    return [
        'ok' => 'OK',
        'warning' => 'Perlu Dicek',
        'error' => 'Masalah',
    ][$status] ?? 'Info';
}

function production_check_row(string $name, string $status, string $detail, string $recommendation): array
{
    return compact('name', 'status', 'detail', 'recommendation');
}

function production_check_writable(string $relativePath): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);

    return production_check_row(
        $relativePath,
        $writable ? 'ok' : 'error',
        $exists ? ($writable ? 'Folder ada dan bisa ditulis.' : 'Folder ada, tetapi belum bisa ditulis oleh PHP.') : 'Folder belum ditemukan.',
        'Pastikan folder dibuat dan permission mengikuti rekomendasi hosting, umumnya 755 atau 775.'
    );
}

$defaultAdminPassword = false;
$adminCount = 0;
if (db_available()) {
    ensure_admin_role_columns();
    $adminCount = (int) db()->query('SELECT COUNT(*) FROM admins WHERE is_active = 1')->fetchColumn();
    $statement = db()->prepare("SELECT password_hash FROM admins WHERE username = 'admin' LIMIT 1");
    $statement->execute();
    $hash = $statement->fetchColumn();
    $defaultAdminPassword = is_string($hash) && password_verify('admin123', $hash);
}

$checks = [
    production_check_row(
        'Versi PHP',
        version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'error',
        'Server memakai PHP ' . PHP_VERSION . '.',
        'Gunakan PHP 8.0 atau lebih baru agar semua fitur native PHP berjalan.'
    ),
    production_check_row(
        'Mode Maintenance',
        empty($content['siteMaintenance']['enabled']) ? 'ok' : 'warning',
        empty($content['siteMaintenance']['enabled']) ? 'Mode maintenance publik sedang tidak aktif.' : 'Mode maintenance publik sedang aktif.',
        'Matikan mode maintenance saat website sudah siap diakses masyarakat.'
    ),
    production_check_row(
        'File .env',
        is_file($envPath) ? 'ok' : 'warning',
        is_file($envPath) ? 'File .env ditemukan.' : 'File .env belum ditemukan, aplikasi memakai nilai default.',
        'Buat .env di hosting berdasarkan .env.example dan isi kredensial database production.'
    ),
    production_check_row(
        'Environment',
        ($appConfig['environment'] ?? 'local') === 'production' ? 'ok' : 'warning',
        'APP_ENV saat ini: ' . ($appConfig['environment'] ?? 'local') . '.',
        'Saat live, gunakan APP_ENV=production.'
    ),
    production_check_row(
        'APP_URL',
        str_starts_with((string) ($appConfig['base_url'] ?? ''), 'https://') ? 'ok' : 'warning',
        'APP_URL saat ini: ' . ($appConfig['base_url'] ?? '-') . '.',
        'Saat live, isi APP_URL dengan domain HTTPS sekolah.'
    ),
    production_check_row(
        'HTTPS',
        $isHttps ? 'ok' : 'warning',
        $isHttps ? 'Request admin sedang memakai HTTPS.' : 'Request admin belum memakai HTTPS.',
        'Aktifkan SSL di hosting dan pakai HTTPS untuk login admin.'
    ),
    production_check_row(
        'Koneksi Database',
        db_available() ? 'ok' : 'error',
        db_available() ? 'Database terhubung ke ' . $dbConfig['host'] . ':' . $dbConfig['port'] . '.' : 'Database belum terhubung.',
        'Cek DB_HOST, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di .env.'
    ),
    production_check_row(
        'Akun Admin Aktif',
        $adminCount > 0 ? 'ok' : 'error',
        $adminCount > 0 ? $adminCount . ' akun admin aktif ditemukan.' : 'Belum ada akun admin aktif.',
        'Pastikan minimal ada satu superadmin aktif sebelum website dipublikasikan.'
    ),
    production_check_row(
        'Password Admin Awal',
        $defaultAdminPassword ? 'error' : 'ok',
        $defaultAdminPassword ? 'Akun admin masih memakai password awal admin123.' : 'Password awal tidak terdeteksi pada akun admin.',
        'Ganti password admin awal sebelum website live.'
    ),
    production_check_row(
        'File .htaccess',
        is_file($htaccessPath) ? 'ok' : 'warning',
        is_file($htaccessPath) ? '.htaccess ditemukan.' : '.htaccess belum ditemukan.',
        'Pastikan .htaccess ikut terupload agar routing dan proteksi file sensitif aktif.'
    ),
    production_check_row(
        'Batas Upload PHP',
        $uploadMax !== '-' && $postMax !== '-' ? 'ok' : 'warning',
        'upload_max_filesize=' . $uploadMax . ', post_max_size=' . $postMax . ', memory_limit=' . $memoryLimit . '.',
        'Sesuaikan batas upload di hosting bila file PPDB atau dokumen resmi sering gagal diunggah.'
    ),
    production_check_row(
        'Optimasi Gambar GD',
        extension_loaded('gd') ? 'ok' : 'warning',
        extension_loaded('gd') ? 'Ekstensi GD aktif. Kompresi gambar otomatis bisa berjalan.' : 'Ekstensi GD belum aktif. Upload tetap valid, tetapi kompresi otomatis tidak berjalan.',
        'Aktifkan ekstensi gd di php.ini hosting/XAMPP untuk kompresi gambar otomatis dan output WEBP bila tersedia.'
    ),
];

$checks = array_merge($checks, [
    production_check_writable('public/uploads'),
    production_check_writable('public/uploads/news'),
    production_check_writable('public/uploads/gallery'),
    production_check_writable('public/uploads/profile'),
    production_check_writable('public/uploads/staff'),
    production_check_writable('public/uploads/facilities'),
    production_check_writable('public/uploads/achievements'),
    production_check_writable('public/uploads/extracurriculars'),
    production_check_writable('storage/uploads'),
    production_check_writable('storage/uploads/ppdb'),
    production_check_writable('storage/uploads/documents'),
]);

$summary = ['ok' => 0, 'warning' => 0, 'error' => 0];
foreach ($checks as $check) {
    $summary[$check['status']]++;
}

require __DIR__ . '/../templates/admin_header.php';
?>
<div class="admin-card">
    <h1>Cek Kesiapan Production</h1>
    <p>Gunakan halaman ini sebelum publish ke hosting untuk memeriksa konfigurasi penting, koneksi database, keamanan dasar, dan folder upload.</p>

    <div class="action-bar">
        <span class="status-badge status-diterima"><?= (int) $summary['ok']; ?> OK</span>
        <span class="status-badge status-diproses"><?= (int) $summary['warning']; ?> perlu dicek</span>
        <span class="status-badge status-ditolak"><?= (int) $summary['error']; ?> masalah</span>
        <a class="button secondary" href="<?= base_url('admin/db_backup.php'); ?>">Backup Database</a>
        <a class="button secondary" href="<?= base_url('admin/change_password.php'); ?>">Ganti Password</a>
    </div>

    <?php if ($summary['error'] > 0): ?>
        <div class="alert error">Ada item penting yang perlu diperbaiki sebelum website dipublikasikan.</div>
    <?php elseif ($summary['warning'] > 0): ?>
        <div class="alert warning">Tidak ada masalah kritis, tetapi masih ada konfigurasi yang perlu dicek sebelum live.</div>
    <?php else: ?>
        <div class="alert success">Semua pemeriksaan utama sudah terlihat siap untuk production.</div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>Hasil Pemeriksaan</h2>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Detail</th>
                    <th>Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                    <tr>
                        <td><strong><?= e($check['name']); ?></strong></td>
                        <td><span class="status-badge <?= production_check_status($check['status']); ?>"><?= e(production_check_label($check['status'])); ?></span></td>
                        <td><?= e($check['detail']); ?></td>
                        <td><?= e($check['recommendation']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../templates/admin_footer.php'; ?>
