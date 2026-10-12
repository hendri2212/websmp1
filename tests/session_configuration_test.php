<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$scenarios = [
    'localhost-http' => ['https' => null, 'port' => '8081', 'secure' => false],
    'localhost-off' => ['https' => 'off', 'port' => '80', 'secure' => false],
    'localhost-uppercase-off' => ['https' => 'OFF', 'port' => 80, 'secure' => false],
    'localhost-zero' => ['https' => '0', 'port' => 80, 'secure' => false],
    'https-on' => ['https' => 'on', 'port' => 8443, 'secure' => true],
    'https-uppercase-on' => ['https' => 'ON', 'port' => 8443, 'secure' => true],
    'https-port-string' => ['https' => null, 'port' => '443', 'secure' => true],
    'https-port-integer' => ['https' => null, 'port' => 443, 'secure' => true],
    'untrusted-forwarded-https' => ['https' => null, 'port' => 8081, 'secure' => false],
];

if (($argv[1] ?? '') === '--probe') {
    $scenario = $scenarios[$argv[2] ?? ''] ?? null;
    if (!$scenario) {
        exit(1);
    }
    $sessionDirectory = __DIR__ . '/.tmp_sessions';
    if (!is_dir($sessionDirectory)) {
        mkdir($sessionDirectory, 0775, true);
    }
    session_save_path($sessionDirectory);
    $_SERVER['REQUEST_URI'] = '/admin/login.php';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_PORT'] = $scenario['port'];
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    if ($scenario['https'] !== null) {
        $_SERVER['HTTPS'] = $scenario['https'];
    } else {
        unset($_SERVER['HTTPS']);
    }
    $fixedId = 'unknown-fixed-id-' . bin2hex(random_bytes(8));
    $_ENV = array_replace($_ENV, ['APP_ENV'=>'local', 'APP_URL'=>'/school', 'DB_HOST'=>'127.0.0.1',
        'DB_PORT'=>'33317', 'DB_DATABASE'=>'synthetic_test', 'DB_USERNAME'=>'synthetic_user',
        'DB_PASSWORD'=>'', 'DB_CHARSET'=>'utf8mb4']);
    $_COOKIE[session_name()] = $fixedId;
    $_GET[session_name()] = $fixedId;
    ob_start();
    require __DIR__ . '/../includes/bootstrap.php';
    $params = session_get_cookie_params();
    $result = [
        'httponly' => $params['httponly'],
        'secure' => $params['secure'],
        'samesite' => $params['samesite'],
        'path' => $params['path'],
        'lifetime' => $params['lifetime'],
        'strict' => ini_get('session.use_strict_mode'),
        'cookie_only' => ini_get('session.use_only_cookies'),
        'url_id_disabled' => ini_get('session.use_trans_sid'),
        'fixation_rejected' => session_id() !== $fixedId,
    ];
    $_SESSION = [];
    session_destroy();
    ob_end_clean();
    echo json_encode($result) . PHP_EOL;
    exit;
}

$checks = 0;
try {
    foreach ($scenarios as $name => $scenario) {
        $process = proc_open([PHP_BINARY, __FILE__, '--probe', $name], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot launch session probe');
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $result = json_decode($output, true);
        if ($exit !== 0 || $errors !== '' || !is_array($result)) {
            throw new RuntimeException('Probe failed: ' . $name . ' ' . $errors);
        }
        $expected = [
            'httponly' => true, 'secure' => $scenario['secure'], 'samesite' => 'Lax',
            'path' => '/', 'lifetime' => 0, 'strict' => '1', 'cookie_only' => '1',
            'url_id_disabled' => '0', 'fixation_rejected' => true,
        ];
        foreach ($expected as $key => $value) {
            if (($result[$key] ?? null) !== $value) {
                throw new RuntimeException($name . ': incorrect ' . $key);
            }
            $checks++;
        }
        echo '[OK] ' . $name . PHP_EOL;
    }
    echo 'Passed checks: ' . $checks . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
