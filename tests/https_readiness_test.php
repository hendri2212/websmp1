<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (is_file(dirname(__DIR__) . '/.env')) {
    fwrite(STDERR, 'HTTPS model requires no root .env; real configuration not read.' . PHP_EOL);
    exit(1);
}

$cases = ['local-http', 'direct-https', 'port-443', 'proxy-unmapped', 'production-http-base'];
if (($argv[1] ?? '') !== '--probe') {
    $total = 0;
    foreach ($cases as $case) {
        $process = proc_open([PHP_BINARY, __FILE__, '--probe', $case], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) { exit(1); }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $status = proc_close($process);
        $result = json_decode($output, true);
        if ($status !== 0 || $errors !== '' || !is_int($result['checks'] ?? null)) {
            fwrite(STDERR, 'HTTPS probe failed: ' . $case . PHP_EOL . $errors);
            exit(1);
        }
        $total += $result['checks'];
    }
    echo 'PASS: ' . $total . ' HTTPS URL/template behavior checks; includes documented gap assertions, not live TLS certification.' . PHP_EOL;
    exit;
}

$case = $argv[2] ?? '';
if (!in_array($case, $cases, true)) { exit(1); }
require dirname(__DIR__) . '/includes/functions.php';
function db_available(): bool { return false; }
function admin_can(string $permission): bool { return true; }

$_ENV['APP_ENV'] = $case === 'local-http' ? 'local' : 'production';
$_ENV['APP_URL'] = match ($case) {
    'local-http' => '/school',
    'production-http-base' => 'http://example.test/school',
    default => 'https://example.test/school',
};
$_SERVER['HTTP_HOST'] = 'localhost:8081';
$_SERVER['REQUEST_URI'] = '/school/profil';
$_SERVER['SERVER_PORT'] = $case === 'port-443' ? 443 : 8081;
$_SERVER['HTTPS'] = $case === 'direct-https' ? 'on' : 'off';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SESSION = [];
$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException('Failed HTTPS model assertion: ' . $label); }
};
$expectedBase = $_ENV['APP_URL'];
$assert(request_is_https() === in_array($case, ['direct-https', 'port-443'], true), 'HTTPS detection including untrusted proxy');
if ($case === 'production-http-base') {
    try {
        app_config();
        throw new LogicException('Production HTTP base URL accepted');
    } catch (RuntimeException $exception) {
        $assert($exception->getMessage() === 'Konfigurasi sistem tidak tersedia. Hubungi pengelola sistem.',
            'Production HTTP URL rejected generically');
    }
    echo json_encode(['checks'=>$checks], JSON_THROW_ON_ERROR);
    exit;
}
foreach (['public/assets/css/style.css', 'admin/login.php', 'admin/logout.php', 'cari', 'dokumen/unduh?id=1'] as $path) {
    $assert(base_url($path) === $expectedBase . '/' . $path, 'base URL ' . $path);
}
$assert(asset('public/logo-sekolah.png') === $expectedBase . '/public/logo-sekolah.png', 'asset URL');
$assert(absolute_url('profil') === ($case === 'local-http' ? 'http://localhost:8081/school/profil' : $expectedBase . '/profil'), 'canonical URL');

$content = require dirname(__DIR__) . '/config/site_data.php';
ob_start();
require dirname(__DIR__) . '/templates/header.php';
require dirname(__DIR__) . '/templates/footer.php';
$publicHtml = ob_get_clean();
ob_start();
require dirname(__DIR__) . '/templates/admin_header.php';
require dirname(__DIR__) . '/templates/admin_footer.php';
$adminHtml = ob_get_clean();
foreach ([$publicHtml, $adminHtml] as $html) {
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $nodes = $xpath->query('//*[@src or @href or @action]');
    $assert($nodes->length > 0, 'rendered URL attributes present');
    foreach ($nodes as $node) {
        foreach (['src', 'href', 'action'] as $attribute) {
            if (!$node->hasAttribute($attribute)) { continue; }
            $url = $node->getAttribute($attribute);
            if ($url === '' || str_starts_with($url, '#')) { continue; }
            $canonical = $attribute === 'href' && $node->getAttribute('rel') === 'canonical';
            $assert($canonical ? $url === absolute_url('profil')
                : (str_starts_with($url, $expectedBase . '/') || $url === $expectedBase), 'rendered internal URL ' . $attribute);
        }
    }
}
$assert(str_contains($publicHtml, 'action="' . $expectedBase . '/cari"'), 'search action');
$assert(str_contains($adminHtml, 'action="' . $expectedBase . '/admin/logout.php"'), 'logout action');
$assert(video_embed_url('http://youtu.be/safe-video') === 'https://www.youtube.com/embed/safe-video', 'YouTube HTTPS normalization');
// Preserve evidence of a gap; accepting HTTP here is not a production security pass.
$assert(video_embed_url('http://example.test/video') === 'http://example.test/video', 'non-YouTube HTTP embed gap');
$assert(safe_content_url('http://example.test/info') === 'http://example.test/info', 'external HTTP navigation accepted');
echo json_encode(['checks' => $checks], JSON_THROW_ON_ERROR);
