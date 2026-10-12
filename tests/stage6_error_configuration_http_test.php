<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = dirname(__DIR__);
$checks = 0;
$process = null;
$files = [];
$directory = null;
$exitCode = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('Failed assertion: ' . $label);
    }
};

try {
    $settings = parse_ini_file($root . '/.user.ini', false, INI_SCANNER_TYPED);
    $assert(is_array($settings), 'user ini parsed');
    $startupSettings = parse_ini_file($root . '/config/php_security.ini', false, INI_SCANNER_TYPED);
    $assert(($startupSettings['expose_php'] ?? null) === false, 'startup ini disables PHP version banner');
    $fallback = in_array('--php-header-fallback', $argv, true);
    foreach (['display_errors' => false, 'display_startup_errors' => false, 'log_errors' => true] as $key => $value) {
        $assert(($settings[$key] ?? null) === $value, 'hosting ini ' . $key);
    }
    $htaccess = file_get_contents($root . '/.htaccess');
    foreach (['display_errors Off', 'display_startup_errors Off', 'log_errors On'] as $directive) {
        $assert(str_contains($htaccess, 'php_flag ' . $directive), 'mod_php ' . $directive);
    }

    $parent = $root . '/storage/testing';
    if (!is_dir($parent) && !mkdir($parent, 0700, true)) {
        throw new RuntimeException('Cannot create private test storage');
    }
    $assert(!is_link($root . '/storage') && !is_link($parent), 'private storage is not a symlink');
    $directory = $parent . '/stage6-error-' . bin2hex(random_bytes(8));
    if (!mkdir($directory, 0700)) {
        throw new RuntimeException('Cannot create test directory');
    }
    $broken = $directory . '/broken.php';
    $log = $directory . '/php-errors.log';
    $stdout = $directory . '/server-output.log';
    $stderr = $directory . '/server-internal.log';
    $files = [$broken, $log, $stdout, $stderr];
    if (file_put_contents($broken, '<?php invalid php STAGE6_PRIVATE_PARSE_BEFORE') === false) {
        throw new RuntimeException('Cannot create controlled parse fixture');
    }
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    if (!$socket) {
        throw new RuntimeException('Cannot reserve loopback port');
    }
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $key = bin2hex(random_bytes(24));
    $env = getenv();
    $env['APP_ENV'] = 'local';
    $env['APP_URL'] = '/school';
    $env['STAGE6_ERROR_TEST_KEY'] = $key;
    $env['STAGE6_ERROR_BROKEN_FILE'] = $broken;
    // PHP's development server does not load .user.ini; apply its verified flags explicitly.
    $command = [PHP_BINARY, '-d', 'display_errors=0', '-d', 'display_startup_errors=0',
        '-d', 'expose_php=' . ($fallback ? '1' : '0'),
        '-d', 'log_errors=1', '-d', 'error_reporting=-1', '-d', 'error_log=' . $log,
        '-S', '127.0.0.1:' . $port, $root . '/tests/fixtures/stage6_error_http_router.php'];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'a'], 2 => ['file', $stderr, 'a']], $pipes, $root, $env);
    $assert(is_resource($process), 'loopback server started');
    fclose($pipes[0]);
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $connection = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
        if ($connection) {
            fclose($connection);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    $assert($ready, 'loopback server ready');
    foreach (['warning', 'native-warning', 'pdo', 'runtime', 'error', 'fatal', 'parse-after', 'parse-before'] as $scenario) {
        $curl = curl_init('http://127.0.0.1:' . $port . '/?scenario=' . $scenario);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => ['X-Test-Key: ' . $key]]);
        $raw = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        $assert(is_string($raw), $scenario . ' HTTP response');
        $body = substr($raw, $headerSize);
        $versionHeader = preg_match('/^X-Powered-By:/mi', substr($raw, 0, $headerSize)) === 1;
        $assert($versionHeader === ($fallback && $scenario === 'parse-before'),
            $scenario . ' PHP banner follows startup/fallback boundary');
        if ($scenario !== 'parse-before') {
            $headers = substr($raw, 0, $headerSize);
            foreach (['X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'X-Frame-Options' => 'SAMEORIGIN', 'Content-Security-Policy' => "frame-ancestors 'self'",
                'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()'] as $name => $value) {
                preg_match_all('/^' . preg_quote($name, '/') . ':\s*([^\r\n]+)/mi', $headers, $matches);
                $assert(count($matches[1]) === 1 && trim($matches[1][0]) === $value, $scenario . ' PHP fallback header ' . $name);
            }
            $assert(!preg_match('/^Strict-Transport-Security:/mi', $headers), $scenario . ' HTTP no HSTS');
        }
        foreach (['STAGE6_PRIVATE', 'mysql:host=', 'SELECT password_hash', 'Stack trace',
            'Fatal error:', 'Warning:', $root, str_replace('\\', '/', $root), 'memory_limit='] as $sensitive) {
            $assert(!str_contains($raw, $sensitive), $scenario . ' response contains no sensitive detail');
        }
        $warning = in_array($scenario, ['warning', 'native-warning'], true);
        $assert($status === ($warning ? 200 : 500), $scenario . ' HTTP status');
        if ($warning) {
            $assert(json_decode($body, true) === ['display_errors' => '0', 'display_startup_errors' => '0', 'log_errors' => '1'], $scenario . ' flags and unpolluted body');
        } elseif ($scenario !== 'parse-before') {
            $assert($body === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.', $scenario . ' generic body and partial output discarded');
            $assert(str_contains($raw, 'Cache-Control: no-store'), $scenario . ' no-store response');
        }
    }
    $internal = is_file($log) ? file_get_contents($log) : '';
    $assert(is_string($internal) && str_contains($internal, 'STAGE6_PRIVATE'), 'controlled warnings retained only in internal log');
    $assert(str_contains($internal, 'Unhandled PDOException'), 'exception class logged internally');
    $assert(!str_contains($internal, 'Unhandled PDOException: STAGE6_PRIVATE'), 'exception message not appended by application logger');
    echo 'PASS: ' . $checks . ' PHP error configuration and HTTP disclosure checks; no database used.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    foreach ($files as $file) {
        if (is_file($file) && !is_link($file)) {
            unlink($file);
        }
    }
    if ($directory && is_dir($directory)) {
        rmdir($directory);
    }
}
exit($exitCode);
