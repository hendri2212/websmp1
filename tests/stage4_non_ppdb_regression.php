<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
if (($argv[1] ?? '') !== '--run') {
    exit('CLI only: --run required.');
}
require __DIR__ . '/stage3_database_preflight.php';
$pdo = stage3_test_database();
$private = realpath(dirname(__DIR__) . '/storage/testing/stage3-mariadb');
$directory = $private . '/regression-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));
mkdir($directory, 0700);
$results = [];

function regression_command(array $command, string $label): string
{
    global $directory, $results;
    $log = $directory . '/' . $label . '.log';
    $process = proc_open($command, [0=>['pipe','r'],1=>['file',$log,'w'],2=>['file',$log,'a']], $pipes, dirname(__DIR__), null, ['bypass_shell'=>true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot run ' . $label);
    }
    fclose($pipes[0]);
    $exit = proc_close($process);
    $output = (string) file_get_contents($log);
    preg_match('/RESULT: (\d+)\/(\d+) passed|Passed(?: assertions| checks| HTTP\/database checks)?: (\d+)|PASS: (\d+)/', $output, $match);
    $summary = $match[0] ?? ($exit === 0 ? 'completed' : 'failed');
    $results[] = ['suite'=>$label,'exit_code'=>$exit,'summary'=>$summary,'log'=>basename($log)];
    echo ($exit === 0 ? 'PASS ' : 'FAIL ') . $label . ': ' . $summary . PHP_EOL;
    if ($exit !== 0) {
        throw new RuntimeException('Regression failed: ' . $label . '; inspect private log.');
    }
    return $output;
}

try {
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if ($table !== 'stage3_test_environment' && (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`','``',$table) . '`')->fetchColumn() !== 0) {
            throw new RuntimeException('Testing database not empty; stop.');
        }
    }
    foreach (['auth_security_test','role_permission_test','session_configuration_test','input_validation_test',
        'strict_id_validation_test','xss_output_security_test','upload_security_test','file_access_test',
        'delete_feedback_test','error_handling_test','legacy_ppdb_database_access_test','stage4_ppdb_dead_code_test'] as $name) {
        regression_command([PHP_BINARY,__DIR__ . '/' . $name . '.php'], $name);
    }
    regression_command([PHP_BINARY,__DIR__ . '/csrf_handler_test.php','--admin-only'], 'csrf_admin');
    regression_command([PHP_BINARY,__DIR__ . '/csrf_handler_test.php'], 'csrf_all_active');
    regression_command([PHP_BINARY,__DIR__ . '/stage3_auth_http_test.php','--prepare'], 'auth_prepare');
    $manifest = json_decode(file_get_contents($private . '/auth-http-run.json'), true, 512, JSON_THROW_ON_ERROR);
    $root = realpath($manifest['snapshot']);
    if (!$root || !str_starts_with($root, $private . DIRECTORY_SEPARATOR) || is_link($manifest['snapshot'])
        || $manifest['url'] !== 'http://127.0.0.1:8087' || $manifest['port'] !== 8087) {
        throw new RuntimeException('Unverified authentication HTTP server target.');
    }
    $server = proc_open([PHP_BINARY,'-S','127.0.0.1:8087','-t',$root,$root . '/router.php'],
        [0=>['pipe','r'],1=>['file',$root . '/server.out.log','a'],2=>['file',$root . '/server.err.log','a']], $pipes, $root, null, ['bypass_shell'=>true]);
    if (!is_resource($server)) {
        throw new RuntimeException('Cannot start owned authentication server.');
    }
    fclose($pipes[0]);
    try {
        $ready = false;
        for ($i=0; $i<50; $i++) {
            $socket = @stream_socket_client('tcp://127.0.0.1:8087', $code, $message, 0.1);
            if (is_resource($socket)) {
                fclose($socket);
                $ready = true;
                break;
            }
            usleep(100000);
        }
        if (!$ready) {
            throw new RuntimeException('Owned authentication server not ready.');
        }
        regression_command([PHP_BINARY,__DIR__ . '/stage3_auth_http_test.php','--run'], 'login_logout_runtime');
    } finally {
        proc_terminate($server);
        proc_close($server);
    }
    regression_command([PHP_BINARY,__DIR__ . '/stage3_auth_http_test.php','--cleanup'], 'auth_fixture_cleanup');
    foreach (['run','roles','uploads','downloads','inputs','ids','validation','consistency','announcements','errors','ppdb-info'] as $mode) {
        regression_command([PHP_BINARY,__DIR__ . '/stage3_content_http_test.php','--' . $mode], 'runtime_' . $mode);
    }
    regression_command([PHP_BINARY,__DIR__ . '/stage3_cleanup_test_data.php','--dry-run'], 'cleanup_plan');
    regression_command([PHP_BINARY,__DIR__ . '/stage3_cleanup_test_data.php','--apply'], 'cleanup_apply');
    regression_command([PHP_BINARY,__DIR__ . '/stage3_cleanup_test_data.php','--dry-run'], 'cleanup_verify');
    file_put_contents($directory . '/result.json', json_encode(['date'=>gmdate('c'),'passed'=>true,'results'=>$results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'PASS: All requested regression suites completed. Evidence: ' . $directory . PHP_EOL;
} catch (Throwable $exception) {
    file_put_contents($directory . '/result.json', json_encode(['date'=>gmdate('c'),'passed'=>false,'results'=>$results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    fwrite(STDERR, 'FAIL: ' . ($exception instanceof PDOException ? 'Isolated database operation failed.' : $exception->getMessage()) . PHP_EOL);
    exit(1);
}
