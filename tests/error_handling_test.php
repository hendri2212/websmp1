<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

const ERROR_SENSITIVE_DETAILS = [
    'mysql:host=SENTINEL_PRIVATE_HOST;dbname=SENTINEL_DATABASE',
    'C:\\SENTINEL_PRIVATE\\config\\database.php',
    "SELECT password_hash FROM admins WHERE username = 'SENTINEL_USERNAME'",
    'memory_limit=SENTINEL_SERVER_CONFIG',
];

if (($argv[1] ?? '') === '--probe') {
    require __DIR__ . '/../includes/error_handling.php';
    configure_web_error_handling();
    configure_web_error_handling();
    $secret = 'SENTINEL_PASSWORD_dbuser_mysql_private_host | ' . implode(' | ', ERROR_SENSITIVE_DETAILS);
    $scenario = $argv[2] ?? '';
    if ($scenario === 'warning') {
        trigger_error($secret, E_USER_WARNING);
        echo json_encode([
            'display_errors' => ini_get('display_errors'),
            'display_startup_errors' => ini_get('display_startup_errors'),
            'log_errors' => ini_get('log_errors'),
        ]);
        exit;
    }
    register_shutdown_function(static function (): void {
        fwrite(STDERR, 'STATUS=' . http_response_code() . PHP_EOL);
    });
    ob_start();
    echo 'Partial sensitive content: ' . $secret;
    if ($scenario === 'fatal') {
        trigger_error($secret, E_USER_ERROR);
    } elseif ($scenario === 'pdo') {
        throw new PDOException($secret);
    } elseif ($scenario === 'error') {
        throw new Error($secret);
    } elseif ($scenario === 'parse') {
        eval('this is invalid php containing ' . $secret);
    } else {
        throw new RuntimeException($secret);
    }
    exit;
}

$checks = 0;
function error_assert(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    foreach (['warning', 'pdo', 'runtime', 'error', 'parse', 'fatal'] as $scenario) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'display_startup_errors=1', __FILE__, '--probe', $scenario], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        error_assert(is_resource($process), $scenario . ': subprocess started');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $log = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        error_assert(!str_contains($output, 'SENTINEL'), $scenario . ': no credential disclosure');
        error_assert(!str_contains($output, 'Stack trace') && !str_contains($output, __DIR__), $scenario . ': no trace or path disclosure');
        foreach (ERROR_SENSITIVE_DETAILS as $detail) {
            error_assert(!str_contains($output, $detail), $scenario . ': no database, path, SQL, or server configuration disclosure');
        }
        if ($scenario === 'warning') {
            error_assert(json_decode($output, true) === ['display_errors' => '0', 'display_startup_errors' => '0', 'log_errors' => '1'], 'Warnings hidden, internal logging retained');
            error_assert(str_contains($log, 'SENTINEL'), 'Warnings remain in internal logs only');
        } else {
            error_assert($output === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.', $scenario . ': exact generic response with partial content removed');
            error_assert(str_contains($log, 'STATUS=500'), $scenario . ': HTTP 500');
            if ($scenario !== 'fatal') {
                error_assert(str_contains($log, 'Unhandled ') && !str_contains($log, 'SENTINEL'), $scenario . ': exception log excludes message and trace arguments');
            }
        }
    }
    echo 'PASS: ' . $checks . ' error disclosure checks.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
