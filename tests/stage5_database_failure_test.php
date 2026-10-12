<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (($argv[1] ?? '') === '--stall-server') {
    $server = stream_socket_server('tcp://127.0.0.1:' . (int) ($argv[2] ?? 0), $code, $message);
    if (!$server) { exit(1); }
    echo "READY\n";
    flush();
    while ($client = @stream_socket_accept($server, 15)) {
        sleep(8);
        fclose($client);
    }
    exit;
}
if (($argv[1] ?? '') !== '--run') { exit("CLI only: --run required.\n"); }
require __DIR__ . '/stage3_database_preflight.php';
$checks = 0;
function failure_check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException('Failed assertion: ' . $label); }
    $checks++;
}
function failure_port(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    if (!$socket) { throw new RuntimeException('Cannot allocate testing port'); }
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    return $port;
}
function failure_http(string $path, string $scenario, ?array $post = null): array
{
    global $base, $key, $cookie;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_TIMEOUT=>12, CURLOPT_COOKIEFILE=>$cookie, CURLOPT_COOKIEJAR=>$cookie,
        CURLOPT_HTTPHEADER=>['X-Test-Key: ' . $key, 'X-Test-Scenario: ' . $scenario]]);
    if ($post !== null) { curl_setopt_array($curl, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query($post)]); }
    $start = microtime(true);
    $raw = curl_exec($curl);
    if (!is_string($raw)) { throw new RuntimeException('Testing HTTP request failed'); }
    $reply = ['status'=>curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'raw'=>$raw,
        'body'=>substr($raw, curl_getinfo($curl, CURLINFO_HEADER_SIZE)), 'elapsed'=>microtime(true)-$start];
    curl_close($curl);
    return $reply;
}
function failure_token(array $reply): string
{
    if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $reply['body'], $match)) { throw new RuntimeException('Missing test CSRF token'); }
    return $match[1];
}
function failure_safe(array $reply): void
{
    global $config, $root, $password, $username;
    foreach ([$config['username'],$config['password'],$config['database'],$root,dirname(__DIR__),$password,
        'mysql:host=','SQLSTATE','PDOException','Stack trace:','Fatal error:','Warning:',
        'SELECT STAGE5_PRIVATE','stage5_missing_table_fixture','STAGE5_PRIVATE_BAD_PASSWORD','memory_limit='] as $secret) {
        failure_check(!str_contains($reply['raw'], $secret), 'No sensitive response detail');
    }
}
function failure_state(PDO $pdo): array
{
    $state = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $quoted = '`' . str_replace('`','``',$table) . '`';
        $state[$table] = [$pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM)[1], $pdo->query('SELECT * FROM ' . $quoted)->fetchAll()];
    }
    return $state;
}

$server = $stall = null;
$pdo = null;
$accountId = null;
try {
    $pdo = stage3_test_database();
    $private = dirname(__DIR__) . '/storage/testing/stage3-mariadb';
    $config = json_decode(file_get_contents($private . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
    failure_check(!$pdo->query("SHOW TABLES LIKE 'stage5_missing_table_fixture'")->fetchColumn(), 'Missing table fixture really absent');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if ($table !== 'stage3_test_environment') { failure_check((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0, 'Testing tables initially empty'); }
    }
    $tag = bin2hex(random_bytes(6));
    $root = $private . '/failure5-' . $tag;
    $key = bin2hex(random_bytes(32));
    $port = failure_port();
    $refusedPort = failure_port();
    $stallPort = failure_port();
    failure_check(count(array_unique([$port,$refusedPort,$stallPort,33317])) === 4, 'Fault ports distinct from database');
    $base = 'http://127.0.0.1:' . $port;
    foreach (['includes','config','admin','pages','templates','storage/sessions'] as $folder) { mkdir($root . '/' . $folder, 0700, true); }
    foreach (['includes','config','pages','templates'] as $folder) {
        foreach (glob(dirname(__DIR__) . '/' . $folder . '/*.php') as $file) { copy($file, $root . '/' . $folder . '/' . basename($file)); }
    }
    foreach (['login','news','logout'] as $name) { copy(dirname(__DIR__) . '/admin/' . $name . '.php', $root . '/admin/' . $name . '.php'); }
    copy(dirname(__DIR__) . '/index.php', $root . '/index.php');
    copy(__DIR__ . '/fixtures/stage5_failure_router.php', $root . '/router.php');
    copy(__DIR__ . '/fixtures/stage5_failure_database.php', $root . '/failure-database.php');
    $source = file_get_contents($root . '/includes/database.php');
    failure_check(substr_count($source, 'function db(): ?PDO') === 1, 'Exactly one connection helper adapted in private snapshot');
    file_put_contents($root . '/includes/database.php', str_replace('function db(): ?PDO', 'function stage5_actual_db(): ?PDO', $source));
    file_put_contents($root . '/config/database.php', '<?php $c=' . var_export($config + ['charset'=>'utf8mb4'], true) . '; $s=$GLOBALS["stage5_scenario"]??"healthy"; if($s==="unavailable"){$c["port"]=' . $refusedPort . ';} elseif($s==="credentials"){$c["password"]="STAGE5_PRIVATE_BAD_PASSWORD";} elseif($s==="handshake-stall"){$c["port"]=' . $stallPort . ';} return $c;');
    file_put_contents($root . '/config/app.php', '<?php return ' . var_export(['app_name'=>'Synthetic testing','environment'=>'local','base_url'=>$base], true) . ';');
    file_put_contents($root . '/manifest.json', json_encode(['key'=>$key,'testing_database'=>$config['database']], JSON_THROW_ON_ERROR));
    $username = 'TEST_TAHAP5_' . $tag;
    $password = bin2hex(random_bytes(24));
    $pdo->prepare('INSERT INTO admins (username,full_name,role,is_active,password_hash) VALUES (?,?,?,?,?)')->execute([$username,'Synthetic testing','superadmin',1,password_hash($password,PASSWORD_DEFAULT)]);
    $accountId = (int) $pdo->lastInsertId();
    $server = proc_open([PHP_BINARY,'-S','127.0.0.1:' . $port,'-t',$root,$root . '/router.php'], [0=>['pipe','r'],1=>['file',$root . '/server.out.log','a'],2=>['file',$root . '/server.err.log','a']], $pipes, $root);
    failure_check(is_resource($server), 'Private HTTP server started'); fclose($pipes[0]);
    $stall = proc_open([PHP_BINARY,__FILE__,'--stall-server',(string) $stallPort], [0=>['pipe','r'],1=>['pipe','w'],2=>['file',$root . '/stall.err.log','a']], $stallPipes);
    failure_check(is_resource($stall), 'Owned loopback handshake stall started'); fclose($stallPipes[0]);
    failure_check(trim((string) fgets($stallPipes[1])) === 'READY', 'Handshake fixture ready'); fclose($stallPipes[1]);
    usleep(400000);
    $evidence = [];
    foreach (['unavailable','credentials','query','missing-table','handshake-stall'] as $scenario) {
        $cookie = $root . '/cookies-' . $scenario . '.txt';
        $token = failure_token(failure_http('/admin/login.php','healthy'));
        $reply = failure_http('/admin/login.php','healthy',['csrf_token'=>$token,'username'=>$username,'password'=>$password]);
        failure_check($reply['status'] === 302, 'Healthy login before fault');
        $before = failure_state($pdo);
        $reply = failure_http('/','' . $scenario);
        failure_check($reply['status'] === (in_array($scenario,['query','missing-table'],true) ? 500 : 503), 'Public fault status');
        failure_check($reply['body'] === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.', 'Exact generic public fault');
        failure_check(str_contains(strtolower($reply['raw']), 'cache-control: no-store'), 'Fault response no-store');
        failure_safe($reply);
        $evidence[$scenario] = ['public_status'=>$reply['status'],'public_seconds'=>round($reply['elapsed'],3)];
        failure_check($reply['elapsed'] < 9, 'Controlled eight-second fixture completed within nine seconds');
        $reply = failure_http('/admin/news.php',$scenario);
        failure_check($reply['status'] === 302 && str_contains($reply['raw'],'/admin/login.php'), 'Stale session direct admin URL denied');
        failure_safe($reply);
        $session = failure_http('/__session','healthy');
        failure_check(json_decode($session['body'],true) === ['authenticated'=>false,'identity'=>false,'role'=>false], 'Fault clears authenticated session fields');
        $reply = failure_http('/admin/news.php',$scenario,['delete_id'=>'1','csrf_token'=>'old-invalid-token']);
        failure_check($reply['status'] === 302, 'Direct administrative POST denied'); failure_safe($reply);
        $token = failure_token(failure_http('/admin/login.php','healthy'));
        $reply = failure_http('/admin/login.php',$scenario,['csrf_token'=>$token,'username'=>$username,'password'=>$password]);
        // Query faults also affect site_content in the real login template: its safe response is 500, not a rendered form.
        $queryFault = in_array($scenario, ['query','missing-table'], true);
        failure_check($queryFault
            ? $reply['status'] === 500 && $reply['body'] === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.'
            : $reply['status'] === 200 && str_contains($reply['body'],'Username atau password tidak sesuai.'), 'Fault cannot create a login session');
        failure_safe($reply);
        failure_check(json_decode(failure_http('/__session','healthy')['body'],true)['authenticated'] === false, 'No authentication after failed login');
        failure_check(failure_state($pdo) === $before, 'Fault requests do not mutate schema or records');
    }
    file_put_contents($root . '/result.json', json_encode(['passed'=>true,'checks'=>$checks,'scenarios'=>$evidence], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'PASS: ' . $checks . ' database failure checks. Evidence: ' . basename($root) . PHP_EOL;
} catch (Throwable $exception) {
    if (isset($root)) {
        file_put_contents($root . '/failure.json', json_encode(['type'=>get_class($exception),
            'assertion'=>str_starts_with($exception->getMessage(), 'Failed assertion: ') ? $exception->getMessage() : 'Harness setup/request failed'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    fwrite(STDERR, 'FAIL: Isolated failure test (' . get_class($exception) . '). Inspect private evidence; no fallback used.' . PHP_EOL);
    $failed = true;
} finally {
    foreach ([$server,$stall] as $process) { if (is_resource($process)) { proc_terminate($process); proc_close($process); } }
    if ($pdo instanceof PDO && $accountId !== null) {
        $pdo->prepare('DELETE FROM admin_activity_logs WHERE username = ?')->execute([$username]);
        $pdo->prepare('DELETE FROM admins WHERE id = ? AND username = ?')->execute([$accountId,$username]);
    }
    if (isset($root)) {
        foreach (glob($root . '/storage/sessions/sess_*') ?: [] as $file) { unlink($file); }
        foreach (glob($root . '/cookies-*.txt') ?: [] as $file) { unlink($file); }
        foreach ([$root . '/config/database.php', $root . '/manifest.json'] as $file) {
            if (is_file($file)) { unlink($file); }
        }
    }
}
if ($failed ?? false) { exit(1); }
