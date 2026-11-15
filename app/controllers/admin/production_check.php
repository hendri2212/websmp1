<?php
require_admin_permission('production_check');

$pageTitle = 'Cek Kesiapan Production';
$appConfig = app_config();
$content = site_content();
$rootPath = APP_ROOT;
$envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';
$isHttps = request_is_https();
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
    $path = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);

    return production_check_row(
        $relativePath,
        $writable ? 'ok' : 'error',
        $exists ? ($writable ? 'Folder ada dan bisa ditulis.' : 'Folder ada, tetapi belum bisa ditulis oleh PHP.') : 'Folder belum ditemukan.',
        'Pastikan folder dibuat dan permission mengikuti rekomendasi hosting, umumnya 755 atau 775.'
    );
}

$adminCount = 0;
if (db_available()) {
    ensure_admin_role_columns();
    $adminCount = active_admin_count();
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
        is_file($envPath) ? 'File .env ditemukan.' : 'File .env tidak ditemukan; konfigurasi wajib berasal dari environment server.',
        'Gunakan environment server atau .env privat dengan konfigurasi production eksplisit.'
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
        db_available() ? 'Database terhubung.' : 'Database belum terhubung.',
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
        'warning',
        'Keamanan password akun existing belum diverifikasi. Credential installer historis memerlukan peninjauan dan rotasi secara privat jika pernah digunakan.',
        'Script inisialisasi membuat akun admin dengan password awal 12345. Ganti password awal melalui menu Ganti Password sebelum release.'
    ),
    production_check_row(
        'Routing dan Proteksi Nginx',
        'warning',
        'Konfigurasi Nginx aktif tidak dapat diverifikasi hanya dari PHP.',
        'Gunakan config/nginx.conf sebagai contoh, lalu uji routing dan penolakan akses folder privat pada server.'
    ),
    production_check_row(
        'Batas Upload PHP',
        $uploadMax !== '-' && $postMax !== '-' ? 'ok' : 'warning',
        'upload_max_filesize=' . $uploadMax . ', post_max_size=' . $postMax . ', memory_limit=' . $memoryLimit . '.',
        'Sesuaikan batas upload di hosting bila dokumen resmi sering gagal diunggah.'
    ),
    production_check_row(
        'Optimasi Gambar GD',
        extension_loaded('gd') ? 'ok' : 'error',
        extension_loaded('gd') ? 'Ekstensi GD aktif. Dukungan JPG, PNG, dan WEBP tetap perlu diverifikasi.' : 'Ekstensi GD belum aktif. Upload gambar ditolak karena validasi dan pemrosesan gambar memerlukan GD.',
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
    production_check_writable('storage/uploads/documents'),
]);

$summary = ['ok' => 0, 'warning' => 0, 'error' => 0];
foreach ($checks as $check) {
    $summary[$check['status']]++;
}

render_view('admin/production_check.php', get_defined_vars(), 'admin');
