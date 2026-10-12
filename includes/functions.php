<?php

require_once __DIR__ . '/input_validation.php';
require_once __DIR__ . '/upload_security.php';
require_once __DIR__ . '/media_lifecycle.php';

function require_http_methods(array $allowed): void
{
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $allowed, true)) {
        return;
    }
    http_response_code(405);
    header('Allow: ' . implode(', ', $allowed));
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit('Metode HTTP tidak diizinkan.');
}

function admin_delete_feedback(callable $delete, string $successMessage): array
{
    try {
        $result = $delete();
        if (is_array($result) && is_bool($result['success'] ?? null) && is_string($result['message'] ?? null)) {
            return $result;
        }
        if ($result === true) {
            return ['success'=>true, 'message'=>$successMessage];
        }
        if ($result !== false) {
            throw new LogicException('Invalid delete result.');
        }
    } catch (MediaRecordNotFoundException $exception) {
        // Missing records are an expected form outcome, not a database error.
    } catch (Throwable $exception) {
        error_log('Administrative delete failed (' . get_class($exception) . ').');
        return ['success'=>false, 'message'=>'Data gagal dihapus. Silakan coba lagi.'];
    }
    return ['success'=>false, 'message'=>'Data tidak ditemukan atau sudah dihapus.'];
}

function database_id(mixed $value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (!is_string($value) || !preg_match('/\A[1-9][0-9]*\z/', $value)) {
        return null;
    }
    $maximum = (string) PHP_INT_MAX;
    if (strlen($value) > strlen($maximum)
        || (strlen($value) === strlen($maximum) && strcmp($value, $maximum) > 0)) {
        return null;
    }
    return (int) $value;
}

function require_database_id(mixed $value): int
{
    $id = database_id($value);
    if ($id === null) {
        throw new InvalidArgumentException('ID tidak valid.');
    }
    return $id;
}

function require_valid_admin_request_ids(): void
{
    foreach (['id', 'edit'] as $key) {
        if (array_key_exists($key, $_GET) && database_id($_GET[$key]) === null) {
            http_response_code(400);
            exit('ID tidak valid.');
        }
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    foreach (['id', 'delete_id', 'message_id'] as $key) {
        if (!array_key_exists($key, $_POST) || ($key === 'id' && $_POST[$key] === '')) {
            continue;
        }
        if (database_id($_POST[$key]) === null) {
            http_response_code(400);
            exit('ID tidak valid.');
        }
    }
}

function app_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim(app_config()['base_url'] ?? '', '/');
    if ($path === '') {
        return $base !== '' ? $base : '/';
    }
    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}

function request_is_https(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    return ($https !== '' && !in_array($https, ['off', '0'], true))
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function absolute_url(string $path = ''): string
{
    $url = base_url($path);
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }

    $scheme = request_is_https() ? 'https' : 'http';
    $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';
    return $scheme . '://' . $host . $url;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Return a raw URL; callers must still use e() when rendering HTML attributes.
function safe_content_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url) || str_starts_with($url, '//')) {
        return '';
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        $parts = parse_url($url);
        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    return str_contains(explode('/', $url, 2)[0], ':') ? '' : $url;
}

function content_link_url(string $url): string
{
    $url = safe_content_url($url);
    if ($url === '') {
        return '#';
    }
    if (preg_match('/^https?:/i', $url) || str_starts_with($url, '#')) {
        return $url;
    }
    return str_starts_with($url, '?') ? base_url() . $url : base_url(ltrim($url, '/'));
}

function asset(string $path): string
{
    return base_url(ltrim($path, '/'));
}

function is_placeholder_image(?string $path): bool
{
    $path = trim((string) $path);
    return $path === '' || str_ends_with($path, '/public/logo-sekolah.png') || $path === 'public/logo-sekolah.png' || $path === '/public/logo-sekolah.png';
}

function initials_from_text(?string $value): string
{
    $words = preg_split('/\s+/', trim((string) $value));
    $initials = '';
    foreach ($words as $word) {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $word);
        if ($clean === '') {
            continue;
        }
        $initials .= strtoupper(substr($clean, 0, 1));
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'SP';
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim($uri, '/');
    $basePath = parse_url(app_config()['base_url'] ?? '', PHP_URL_PATH) ?: '';
    $basePath = '/' . trim($basePath, '/');

    if ($basePath !== '/' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath)) ?: '/';
    }

    if ($path === '/index.php') {
        $path = '/';
    }

    return rtrim($path, '/') === '' ? '/' : rtrim($path, '/');
}

function is_active_path(string $path): bool
{
    return current_path() === $path;
}

function plain_text_excerpt(string $value, int $maxLength = 160): string
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($value)));
    $length = function_exists('mb_strlen') ? mb_strlen($plain) : strlen($plain);
    if ($length <= $maxLength) {
        return $plain;
    }

    $excerpt = function_exists('mb_substr') ? mb_substr($plain, 0, $maxLength - 1) : substr($plain, 0, $maxLength - 1);
    return rtrim($excerpt) . '...';
}

function youtube_video_id(string $url): ?string
{
    $url = safe_content_url($url);
    if ($url === '') { return null; }
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $path = $parts['path'] ?? '';
    if (!in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)
        || isset($parts['port']) || isset($parts['user']) || isset($parts['pass'])) { return null; }
    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $id = null;
    if ($host === 'youtu.be' && preg_match('~\A/([a-zA-Z0-9_-]{11})/?\z~', $path, $match)) {
        $id = $match[1];
    } elseif (in_array($host, ['youtube.com','www.youtube.com','m.youtube.com'], true) && $path === '/watch') {
        $id = is_string($query['v'] ?? null) ? $query['v'] : null;
    } elseif (preg_match('~\A/(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})/?\z~', $path, $match)) {
        $id = $match[1];
    }
    return is_string($id) && preg_match('/\A[a-zA-Z0-9_-]{11}\z/', $id) ? $id : null;
}

function video_embed_url(string $url): string
{
    $id = youtube_video_id($url);
    return $id === null ? '' : 'https://www.youtube.com/embed/' . $id;
}

function profile_video_upload_path(string $url): ?string
{
    $url = safe_content_url($url);
    if ($url === '') { return null; }
    $parts = parse_url($url);
    $localPath = $parts['path'] ?? '';
    if (isset($parts['host'])) {
        $base = parse_url(base_url());
        if (strtolower($parts['host']) !== strtolower($base['host'] ?? '')
            || ($parts['scheme'] ?? '') !== ($base['scheme'] ?? '')
            || ($parts['port'] ?? null) !== ($base['port'] ?? null)) { return null; }
        $basePath = rtrim($base['path'] ?? '', '/');
        if ($basePath !== '') {
            if (!str_starts_with($localPath, $basePath . '/')) { return null; }
            $localPath = substr($localPath, strlen($basePath));
        }
    }
    $path = managed_media_path($localPath);
    return $path !== null && str_starts_with($path, 'public/uploads/videos/') ? '/' . $path : null;
}

function video_file_url(string $url): string
{
    $url = safe_content_url($url);
    if ($url === '') {
        return '';
    }
    $path = rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?? ''));
    if (preg_match('/[\x00-\x1f\x7f\\\\]/', $path)
        || preg_match('~(?:^|/)\.\.?(?:/|$)~', $path)
        || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm', 'ogv'], true)) {
        return '';
    }
    return content_link_url($url);
}

function normalize_hex_color(?string $value, string $fallback): string
{
    $value = trim((string) $value);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtolower($value);
    }

    return $fallback;
}

function theme_css_variables(array $theme = []): string
{
    $colors = [
        '--sky' => normalize_hex_color($theme['primaryColor'] ?? null, '#0284c7'),
        '--sky-dark' => normalize_hex_color($theme['primaryDarkColor'] ?? null, '#0b4fa8'),
        '--cyan' => normalize_hex_color($theme['secondaryColor'] ?? null, '#06b6d4'),
        '--gold' => normalize_hex_color($theme['accentColor'] ?? null, '#eab308'),
        '--navy' => normalize_hex_color($theme['darkColor'] ?? null, '#0f172a'),
    ];

    $declarations = [];
    foreach ($colors as $key => $value) {
        $declarations[] = $key . ': ' . $value;
    }

    return ':root {' . implode('; ', $declarations) . ';}';
}

function redirect_to(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: frame-ancestors 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    if (str_starts_with(current_path(), '/admin')) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

function safe_download_filename(string $filename, string $fallback = 'download'): string
{
    $filename = basename(str_replace(["\r", "\n", '"', "'", '\\'], '', $filename));
    $filename = preg_replace('/[^a-zA-Z0-9._ -]/', '-', $filename);
    $filename = trim((string) $filename, " .-_");

    return $filename !== '' ? $filename : $fallback;
}

function file_is_within_directory(string $filePath, string $directory): bool
{
    $file = realpath($filePath);
    $root = realpath($directory);
    if ($file === false || $root === false || !is_file($file) || !is_dir($root)) {
        return false;
    }

    $file = str_replace('\\', '/', $file);
    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    if (PHP_OS_FAMILY === 'Windows') {
        $file = strtolower($file);
        $root = strtolower($root);
    }

    return str_starts_with($file, $root);
}

function csv_safe_cell(mixed $value): string
{
    $value = preg_replace('/\R+/', ' ', (string) $value);
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
        return "'" . $value;
    }

    return $value;
}

function csrf_token(): string
{
    if (!is_string($_SESSION['csrf_token'] ?? null) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_token_is_valid(mixed $token): bool
{
    return is_string($token)
        && $token !== ''
        && is_string($_SESSION['csrf_token'] ?? null)
        && hash_equals($_SESSION['csrf_token'], $token);
}

const IMAGE_UPLOAD_MAX_BYTES = 20971520;

function file_size_label(int $bytes): string
{
    if ($bytes >= 1048576) {
        $value = $bytes / 1048576;
        return rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',') . ' MB';
    }

    if ($bytes >= 1024) {
        $value = $bytes / 1024;
        return rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',') . ' KB';
    }

    return $bytes . ' B';
}

function image_upload_help_text(int $maxBytes = IMAGE_UPLOAD_MAX_BYTES): string
{
    return 'Format JPG, PNG, atau WEBP. Maksimal ' . file_size_label($maxBytes) . '. Rekomendasi rasio 16:10 atau 4:3, minimal 1200 px lebar. Gambar besar akan dioptimalkan otomatis.';
}

function publication_status_label(int|string|null $isActive): string
{
    return (int) $isActive === 1 ? 'Publish' : 'Draft';
}

function publication_status_class(int|string|null $isActive): string
{
    return (int) $isActive === 1 ? 'status-selesai' : 'status-diproses';
}

function image_resource_from_upload(string $path, string $mimeType): mixed
{
    if ($mimeType === 'image/webp' && !upload_webp_container_is_complete($path)) {
        return false;
    }
    $jpegWarnings = ini_get('gd.jpeg_ignore_warning');
    if ($mimeType === 'image/jpeg' && ini_set('gd.jpeg_ignore_warning', '0') === false) {
        return false;
    }
    $warning = false;
    set_error_handler(static function () use (&$warning): bool { $warning = true; return true; });
    try {
        $resource = match ($mimeType) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
        };
    } catch (Throwable $exception) {
        $resource = false;
    } finally {
        restore_error_handler();
        if ($mimeType === 'image/jpeg' && $jpegWarnings !== false) {
            ini_set('gd.jpeg_ignore_warning', $jpegWarnings);
        }
    }
    if ($warning && $resource) {
        imagedestroy($resource);
        return false;
    }
    return $resource;
}

function save_optimized_image(string $sourcePath, string $destination, string $mimeType, int $width, int $height, int $maxDimension = 1600): bool
{
    $source = image_resource_from_upload($sourcePath, $mimeType);
    if (!$source) {
        return false;
    }

    $ratio = min(1, $maxDimension / max($width, $height));
    $targetWidth = max(1, (int) round($width * $ratio));
    $targetHeight = max(1, (int) round($height * $ratio));
    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
    imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $saved = false;
    if (function_exists('imagewebp')) {
        $saved = imagewebp($canvas, $destination, 82);
    } elseif ($mimeType === 'image/png' && function_exists('imagepng')) {
        $saved = imagepng($canvas, $destination, 6);
    } elseif ($mimeType === 'image/jpeg' && function_exists('imagejpeg')) {
        $saved = imagejpeg($canvas, $destination, 84);
    }

    imagedestroy($canvas);
    imagedestroy($source);

    return $saved;
}

function image_thumbnail_path(?string $path): ?string
{
    $path = trim((string) $path);
    if ($path === '' || is_placeholder_image($path)) {
        return null;
    }

    $normalized = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $info = pathinfo($normalized);
    $directory = $info['dirname'] ?? '';
    $filename = $info['basename'] ?? '';
    if ($directory === '' || $filename === '') {
        return null;
    }

    $thumbRelative = ltrim($directory . '/thumbs/' . $filename, '/');
    $thumbAbsolute = __DIR__ . '/../' . $thumbRelative;
    return file_exists($thumbAbsolute) ? '/' . $thumbRelative : $normalized;
}

function image_asset_path(?string $path, bool $preferThumbnail = true): string
{
    $imagePath = $preferThumbnail ? image_thumbnail_path($path) : null;
    return asset(ltrim($imagePath ?: ($path ?: '/public/logo-sekolah.png'), '/'));
}

function uploaded_image_result(string $field, string $folder, int $maxBytes = IMAGE_UPLOAD_MAX_BYTES): array
{
    if (!isset($_FILES[$field])) {
        return ['path' => null, 'error' => null];
    }
    $fail = static function (string $error): array {
        rollback_uploaded_files();
        return ['path' => null, 'error' => $error];
    };
    $file = $_FILES[$field];
    if (!is_array($file)) {
        return $fail('Metadata upload tidak valid.');
    }
    $nameError = upload_filename_error($file, ['jpg', 'jpeg', 'png', 'webp']);
    if ($nameError !== '') {
        return $fail($nameError);
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return $fail('Upload gambar gagal atau tidak valid.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0 || $size > $maxBytes) {
        return $fail('Ukuran gambar melebihi batas atau file kosong.');
    }
    if (!class_exists('finfo') || !function_exists('imagecreatetruecolor')) {
        return $fail('Validasi gambar belum tersedia pada server.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>['jpg','jpeg'], 'image/png'=>['png'], 'image/webp'=>['webp']];
    $info = @getimagesize($file['tmp_name']);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($extensions[$mime]) || !in_array($extension, $extensions[$mime], true)
        || !$info || ($info['mime'] ?? '') !== $mime) {
        return $fail('Ekstensi dan konten gambar harus sesuai JPG, PNG, atau WEBP.');
    }
    $width = (int) $info[0];
    $height = (int) $info[1];
    if (!upload_image_fits_memory($width, $height)) {
        return $fail('Dimensi gambar tidak sesuai atau terlalu besar untuk diproses.');
    }
    if (!preg_match('/\A[a-zA-Z0-9_-]+\z/', $folder)) {
        return $fail('Folder upload tidak valid.');
    }
    $uploadDir = __DIR__ . '/../public/uploads/' . $folder;
    $thumbDir = $uploadDir . '/thumbs';
    if ((!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true))
        || (!is_dir($thumbDir) && !mkdir($thumbDir, 0755, true))) {
        return $fail('Folder upload belum bisa dibuat.');
    }
    $outputExtension = function_exists('imagewebp') ? 'webp' : $extensions[$mime][0];
    $filename = $folder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $outputExtension;
    $relative = 'public/uploads/' . $folder . '/' . $filename;
    $destination = __DIR__ . '/../' . $relative;
    $thumbnail = $thumbDir . '/' . $filename;
    if (!reserve_uploaded_file($destination) || !reserve_uploaded_file($thumbnail)
        || !save_optimized_image($file['tmp_name'], $destination, $mime, $width, $height)
        || !save_optimized_image($file['tmp_name'], $thumbnail, $mime, $width, $height, 640)
        || filesize($destination) === 0 || filesize($thumbnail) === 0) {
        return $fail('Gambar rusak atau gagal diproses. Upload tidak disimpan.');
    }
    return ['path' => '/' . $relative, 'error' => null];
}

function uploaded_image_path(string $field, string $folder): ?string
{
    return uploaded_image_result($field, $folder)['path'];
}



function uploaded_profile_video_result(string $field): array
{
    $empty = ['path'=>null, 'error'=>null];
    if (!isset($_FILES[$field])) { return $empty; }
    $fail = static function (string $error) use ($empty): array {
        rollback_uploaded_files();
        return array_replace($empty, ['error'=>$error]);
    };
    $file = $_FILES[$field];
    if (!is_array($file)) { return $fail('Metadata upload video tidak valid.'); }
    $nameError = upload_filename_error($file, ['mp4', 'mov']);
    if ($nameError !== '') { return $fail($nameError); }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) { return $empty; }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return $fail('Upload video gagal. Pilih MP4 atau MOV maksimal 20 MB.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0 || $size > 20 * 1048576) {
        return $fail('Video harus berukuran maksimal 20 MB dan tidak kosong.');
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedMimes = $extension === 'mov' ? ['video/quicktime', 'video/mp4'] : ['video/mp4'];
    if (!class_exists('finfo') || !in_array((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), $allowedMimes, true)
        || !uploaded_video_has_basic_structure($file['tmp_name'], $extension === 'mov')) {
        return $fail('Konten video tidak sesuai MP4/MOV atau struktur file rusak.');
    }
    $directory = __DIR__ . '/../public/uploads/videos';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        return $fail('Penyimpanan video belum tersedia.');
    }
    $relative = 'public/uploads/videos/videos-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = __DIR__ . '/../' . $relative;
    if (!reserve_uploaded_file($destination) || !move_uploaded_file($file['tmp_name'], $destination)) {
        return $fail('Video gagal disimpan. Silakan coba lagi.');
    }
    return ['path'=>'/' . $relative, 'error'=>null];
}

function uploaded_pdf_result(string $field, string $folder, int $maxBytes = 5242880): array
{
    $empty = ['path'=>null, 'error'=>null, 'original_filename'=>null, 'file_size'=>0];
    if (!isset($_FILES[$field])) {
        return $empty;
    }
    $fail = static function (string $error) use ($empty): array {
        rollback_uploaded_files();
        return array_replace($empty, ['error'=>$error]);
    };
    $file = $_FILES[$field];
    if (!is_array($file)) {
        return $fail('Metadata upload tidak valid.');
    }
    $nameError = upload_filename_error($file, ['pdf']);
    if ($nameError !== '') {
        return $fail($nameError);
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $empty;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return $fail('Upload PDF gagal atau tidak valid.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0 || $size > $maxBytes) {
        return $fail('Ukuran PDF melebihi batas atau file kosong.');
    }
    if (!class_exists('finfo') || (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== 'application/pdf'
        || !uploaded_pdf_has_basic_structure($file['tmp_name'])) {
        return $fail('Konten PDF tidak valid atau rusak.');
    }
    if (!preg_match('/\A[a-zA-Z0-9_-]+\z/', $folder)) {
        return $fail('Folder upload tidak valid.');
    }
    $uploadDir = __DIR__ . '/../storage/uploads/' . $folder;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return $fail('Folder penyimpanan PDF belum bisa dibuat.');
    }
    $filename = $folder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.pdf';
    $relative = 'storage/uploads/' . $folder . '/' . $filename;
    $destination = __DIR__ . '/../' . $relative;
    if (!reserve_uploaded_file($destination) || !move_uploaded_file($file['tmp_name'], $destination)) {
        return $fail('PDF gagal disimpan.');
    }
    return ['path'=>$relative, 'error'=>null, 'original_filename'=>$file['name'], 'file_size'=>$size];
}
