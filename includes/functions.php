<?php

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

function absolute_url(string $path = ''): string
{
    $url = base_url($path);
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';
    return $scheme . '://' . $host . $url;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function video_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $path = $parts['path'] ?? '';
    $query = [];
    parse_str($parts['query'] ?? '', $query);

    if (str_contains($host, 'youtube.com')) {
        if (!empty($query['v'])) {
            return 'https://www.youtube.com/embed/' . rawurlencode($query['v']);
        }
        if (preg_match('#^/(embed|shorts)/([^/?]+)#', $path, $matches)) {
            return 'https://www.youtube.com/embed/' . rawurlencode($matches[2]);
        }
    }

    if (str_contains($host, 'youtu.be') && preg_match('#^/([^/?]+)#', $path, $matches)) {
        return 'https://www.youtube.com/embed/' . rawurlencode($matches[1]);
    }

    return $url;
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

    header('X-Frame-Options: SAMEORIGIN');
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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_token_is_valid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
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
    return match ($mimeType) {
        'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
        'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
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
    if (empty($_FILES[$field]['name'])) {
        return ['path' => null, 'error' => null];
    }

    $uploadError = $_FILES[$field]['error'] ?? UPLOAD_ERR_OK;
    if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        return ['path' => null, 'error' => 'Ukuran gambar maksimal ' . file_size_label($maxBytes) . '. Pastikan upload_max_filesize dan post_max_size server tidak lebih kecil dari batas ini.'];
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Upload gambar gagal. Silakan coba lagi.'];
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return ['path' => null, 'error' => 'File upload tidak valid.'];
    }

    if (($_FILES[$field]['size'] ?? 0) > $maxBytes) {
        return ['path' => null, 'error' => 'Ukuran gambar maksimal ' . file_size_label($maxBytes) . '.'];
    }

    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);
        }
    }

    $imageInfo = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$mimeType && is_array($imageInfo)) {
        $mimeType = $imageInfo['mime'] ?? null;
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimeTypes[$mimeType]) || $imageInfo === false) {
        return ['path' => null, 'error' => 'Format gambar harus JPG, PNG, atau WEBP yang valid.'];
    }

    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 400 || $height < 300) {
        return ['path' => null, 'error' => 'Resolusi gambar terlalu kecil. Gunakan gambar minimal 400 x 300 piksel.'];
    }

    $safeFolder = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $folder), '/');
    if ($safeFolder === '') {
        return ['path' => null, 'error' => 'Folder upload tidak valid.'];
    }

    $uploadDir = __DIR__ . '/../public/uploads/' . $safeFolder;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['path' => null, 'error' => 'Folder upload belum bisa dibuat.'];
    }
    $thumbDir = $uploadDir . '/thumbs';
    if (!is_dir($thumbDir) && !mkdir($thumbDir, 0755, true)) {
        return ['path' => null, 'error' => 'Folder thumbnail belum bisa dibuat.'];
    }

    $extension = function_exists('imagewebp') ? 'webp' : $allowedMimeTypes[$mimeType];
    $filename = $safeFolder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $relative = 'public/uploads/' . $safeFolder . '/' . $filename;
    $destination = __DIR__ . '/../' . $relative;
    $thumbDestination = $thumbDir . '/' . $filename;

    $optimized = false;
    if (function_exists('imagecreatetruecolor')) {
        $optimized = save_optimized_image($_FILES[$field]['tmp_name'], $destination, $mimeType, $width, $height);
        if ($optimized) {
            save_optimized_image($_FILES[$field]['tmp_name'], $thumbDestination, $mimeType, $width, $height, 640);
        }
    }

    if (!$optimized) {
        $fallbackDestination = __DIR__ . '/../public/uploads/' . $safeFolder . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.' . $allowedMimeTypes[$mimeType];
        $relative = 'public/uploads/' . $safeFolder . '/' . basename($fallbackDestination);
        $thumbDestination = $thumbDir . '/' . basename($fallbackDestination);

        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $fallbackDestination)) {
            return ['path' => null, 'error' => 'Gambar gagal disimpan ke folder upload.'];
        }
    } elseif (!file_exists($thumbDestination)) {
        @copy($destination, $thumbDestination);
    }

    return ['path' => '/' . str_replace('\\', '/', $relative), 'error' => null];
}

function uploaded_image_path(string $field, string $folder): ?string
{
    return uploaded_image_result($field, $folder)['path'];
}

function uploaded_document_result(string $field, string $folder, int $maxBytes = 3145728): array
{
    if (empty($_FILES[$field]['name'])) {
        return ['path' => null, 'error' => null];
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Upload berkas gagal. Silakan coba lagi.'];
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return ['path' => null, 'error' => 'Berkas upload tidak valid.'];
    }

    if (($_FILES[$field]['size'] ?? 0) > $maxBytes) {
        return ['path' => null, 'error' => 'Ukuran berkas maksimal 3 MB.'];
    }

    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);
        }
    }

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        return ['path' => null, 'error' => 'Berkas harus berupa PDF, JPG, PNG, atau WEBP.'];
    }

    $safeFolder = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $folder), '/');
    if ($safeFolder === '') {
        return ['path' => null, 'error' => 'Folder upload tidak valid.'];
    }

    $uploadDir = __DIR__ . '/../storage/uploads/' . $safeFolder;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['path' => null, 'error' => 'Folder penyimpanan berkas belum bisa dibuat.'];
    }

    $filename = $safeFolder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $allowedMimeTypes[$mimeType];
    $relative = 'storage/uploads/' . $safeFolder . '/' . $filename;
    $destination = __DIR__ . '/../' . $relative;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
        return ['path' => null, 'error' => 'Berkas gagal disimpan.'];
    }

    return ['path' => str_replace('\\', '/', $relative), 'error' => null];
}

function uploaded_pdf_result(string $field, string $folder, int $maxBytes = 5242880): array
{
    if (empty($_FILES[$field]['name'])) {
        return ['path' => null, 'error' => null, 'original_filename' => null, 'file_size' => 0];
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Upload PDF gagal. Silakan coba lagi.', 'original_filename' => null, 'file_size' => 0];
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return ['path' => null, 'error' => 'File PDF upload tidak valid.', 'original_filename' => null, 'file_size' => 0];
    }

    $fileSize = (int) ($_FILES[$field]['size'] ?? 0);
    if ($fileSize > $maxBytes) {
        return ['path' => null, 'error' => 'Ukuran PDF maksimal 5 MB.', 'original_filename' => null, 'file_size' => 0];
    }

    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mimeType !== 'application/pdf') {
        return ['path' => null, 'error' => 'Dokumen harus berupa file PDF.', 'original_filename' => null, 'file_size' => 0];
    }

    $safeFolder = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $folder), '/');
    if ($safeFolder === '') {
        return ['path' => null, 'error' => 'Folder upload tidak valid.', 'original_filename' => null, 'file_size' => 0];
    }

    $uploadDir = __DIR__ . '/../storage/uploads/' . $safeFolder;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['path' => null, 'error' => 'Folder penyimpanan PDF belum bisa dibuat.', 'original_filename' => null, 'file_size' => 0];
    }

    $filename = $safeFolder . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.pdf';
    $relative = 'storage/uploads/' . $safeFolder . '/' . $filename;
    $destination = __DIR__ . '/../' . $relative;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
        return ['path' => null, 'error' => 'PDF gagal disimpan.', 'original_filename' => null, 'file_size' => 0];
    }

    return [
        'path' => str_replace('\\', '/', $relative),
        'error' => null,
        'original_filename' => basename($_FILES[$field]['name']),
        'file_size' => $fileSize,
    ];
}
