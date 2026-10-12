<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

function configuration_test_cases(): array
{
    $local = ['APP_ENV'=>'local', 'APP_URL'=>'/school', 'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>'33317',
        'DB_DATABASE'=>'synthetic_test', 'DB_USERNAME'=>'synthetic_user', 'DB_PASSWORD'=>'', 'DB_CHARSET'=>'utf8mb4'];
    // Reserved DNS names and dummy values: the PDO boundary never opens a socket.
    $production = array_replace($local, ['APP_ENV'=>'production', 'APP_URL'=>'https://school.example.invalid',
        'DB_HOST'=>'db.example.invalid', 'DB_DATABASE'=>'school_content', 'DB_PASSWORD'=>'SYNTHETIC_ONLY_NOT_A_CREDENTIAL']);
    $cases = ['local'=>[$local,true], 'production'=>[$production,true]];
    foreach (array_keys($local) as $key) {
        $missing = $production; unset($missing[$key]);
        $cases['missing-' . $key] = [$missing,false];
        $cases['empty-' . $key] = [array_replace($production, [$key=>'']),false];
    }
    foreach (['localhost','127.0.0.1','127.1.2.3','::1','0.0.0.0','localhost.','db.localhost',
        '::','::ffff:127.0.0.1','::ffff:7f00:1','2130706433','0x7f000001'] as $host) {
        $cases['loopback-' . count($cases)] = [array_replace($production, ['DB_HOST'=>$host]),false];
    }
    foreach (['root','ROOT'] as $user) { $cases['user-' . $user] = [array_replace($production,['DB_USERNAME'=>$user]),false]; }
    foreach (['website_sekolah','school_dev','school_test_stage3','development','school;port=3306'] as $name) {
        $cases['database-' . count($cases)] = [array_replace($production,['DB_DATABASE'=>$name]),false];
    }
    foreach (['3306abc','1e2','0','65536','-1','3306.0'] as $port) {
        $cases['port-' . count($cases)] = [array_replace($production,['DB_PORT'=>$port]),false];
    }
    foreach (['http://school.example.invalid','/school','//school.example.invalid','https://user:pass@school.example.invalid',
        "https://school.example.invalid/\r\nX-Test: bad", 'https://localhost', 'https://school.example.invalid/?x=1'] as $url) {
        $cases['url-' . count($cases)] = [array_replace($production,['APP_URL'=>$url]),false];
    }
    foreach (['DB_HOST'=>'db.example.invalid;port=3306','DB_CHARSET'=>'utf8mb4;bad=1','APP_ENV'=>'prod',
        'DB_PASSWORD'=>"dummy\0value", 'DB_USERNAME'=>['root']] as $key=>$value) {
        $cases['invalid-' . $key] = [array_replace($production,[$key=>$value]),false];
    }
    $cases['server-production-over-file-local'] = [$production,true];
    return $cases;
}

function configuration_test_environment(array $values): void
{
    foreach (array_keys(configuration_test_cases()['local'][0]) as $key) {
        unset($_ENV[$key], $_SERVER[$key]); putenv($key);
    }
    $_ENV = array_replace($_ENV, $values);
}

if (($argv[1] ?? '') === '--probe') {
    $directory = realpath($argv[2] ?? '');
    $private = realpath(dirname(__DIR__) . '/storage/testing');
    if (!$directory || !$private || !str_starts_with($directory, $private . DIRECTORY_SEPARATOR)) { exit(1); }
    $case = configuration_test_cases()[$argv[3] ?? ''] ?? null;
    if (!$case) { exit(1); }
    configuration_test_environment($case[0]);
    final class Stage7NoNetworkPDO extends PDO
    {
        public static int $attempts = 0;
        public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
        {
            self::$attempts++;
            throw new PDOException('SYNTHETIC_PRIVATE_CONNECTION_EXCEPTION');
        }
    }
    $valid = false; $message = '';
    try { require $directory . '/config/app.php'; require $directory . '/config/database.php'; $valid = true; }
    catch (Throwable $exception) { $message = $exception->getMessage(); }
    require $directory . '/includes/database.php';
    ob_start(); $unavailable = db() === null && db_available() === false; $output = ob_get_clean();
    echo json_encode(['valid'=>$valid,'attempts'=>Stage7NoNetworkPDO::$attempts,'unavailable'=>$unavailable,
        'message'=>$message,'output'=>$output], JSON_THROW_ON_ERROR);
    exit;
}

$root = dirname(__DIR__); $directory = null; $process = null; $checks = 0; $failed = false;
$assert = static function (bool $condition) use (&$checks): void {
    if (!$condition) { throw new RuntimeException('Configuration assertion failed'); } $checks++;
};
try {
    $parent = realpath($root . '/storage/testing');
    $assert(is_string($parent) && !is_link($parent));
    $directory = $parent . '/stage7-config-' . bin2hex(random_bytes(8));
    mkdir($directory,0700); mkdir($directory . '/config'); mkdir($directory . '/includes');
    foreach (['env.php','app.php','database.php'] as $file) { copy($root . '/config/' . $file,$directory . '/config/' . $file); }
    foreach (glob($root . '/includes/*.php') as $file) { copy($file,$directory . '/includes/' . basename($file)); }
    $databaseSource = file_get_contents($directory . '/includes/database.php');
    $assert(substr_count($databaseSource,'new PDO(') === 1);
    file_put_contents($directory . '/includes/database.php',str_replace('new PDO(','new Stage7NoNetworkPDO(',$databaseSource));
    foreach (configuration_test_cases() as $name=>[$values,$expected]) {
        file_put_contents($directory . '/.env', $name === 'server-production-over-file-local' ? "APP_ENV=local\nDB_HOST=localhost\nDB_USERNAME=root\nDB_PASSWORD=\n" : '');
        $child = proc_open([PHP_BINARY,__FILE__,'--probe',$directory,$name],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        $assert(is_resource($child));
        $result = json_decode(stream_get_contents($pipes[1]),true); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($child);
        $assert($exit === 0 && $errors === '' && is_array($result));
        $assert($result['valid'] === $expected);
        $assert($result['attempts'] === ($expected ? 1 : 0));
        $assert($result['unavailable'] && $result['output'] === '');
        $assert($result['message'] === ($expected ? '' : 'Konfigurasi sistem tidak tersedia. Hubungi pengelola sistem.'));
    }
    file_put_contents($directory . '/.env','');
    file_put_contents($directory . '/cases.json',json_encode(configuration_test_cases(),JSON_THROW_ON_ERROR));
    mkdir($directory . '/sessions',0700);
    // HTTP uses the unchanged bootstrap/config source; no request calls db().
    $router = '<?php $cases=json_decode(file_get_contents(__DIR__."/cases.json"),true);'
        . '$case=$cases[$_GET["case"]??""]??null;if(!$case){http_response_code(404);exit;}'
        . 'foreach(array_keys($cases["local"][0]) as $key){unset($_ENV[$key],$_SERVER[$key]);putenv($key);}'
        . '$_ENV=array_replace($_ENV,$case[0]);session_save_path(__DIR__."/sessions");require __DIR__."/includes/bootstrap.php";'
        . 'header("Content-Type: application/json");echo json_encode(["ready"=>true,"secure"=>session_get_cookie_params()["secure"]]);';
    file_put_contents($directory . '/router.php',$router);
    $socket = stream_socket_server('tcp://127.0.0.1:0',$code,$message); $assert(is_resource($socket));
    $port = (int) substr(strrchr(stream_socket_get_name($socket,false),':'),1); fclose($socket);
    $process = proc_open([PHP_BINARY,'-S','127.0.0.1:' . $port,'-t',$directory,$directory . '/router.php'],
        [0=>['pipe','r'],1=>['file',$directory . '/server.log','a'],2=>['file',$directory . '/error.log','a']],$pipes,$directory);
    $assert(is_resource($process)); fclose($pipes[0]); usleep(400000);
    foreach (configuration_test_cases() as $name=>[$values,$expected]) {
        foreach (['/','/admin/login.php','/admin/news.php'] as $path) {
            $curl=curl_init('http://127.0.0.1:' . $port . $path . '?case=' . rawurlencode($name));
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>5]);
            $raw=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
            $headerLength=curl_getinfo($curl,CURLINFO_HEADER_SIZE); curl_close($curl);
            $assert(is_string($raw)); $body=substr($raw,$headerLength);
            $assert($status === ($expected ? 200 : 503));
            $assert($expected ? json_decode($body,true) === ['ready'=>true,'secure'=>false]
                : $body === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.');
            $assert($expected || (!str_contains($raw,'Set-Cookie:') && !str_contains($raw,$directory)
                && !str_contains($raw,'SYNTHETIC') && str_contains(strtolower($raw),'cache-control: no-store')));
        }
    }
    echo 'PASS: ' . $checks . ' configuration/HTTP checks; ' . count(configuration_test_cases()) . ' cases; zero network database connections.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR,'FAIL: configuration test stopped safely (' . get_class($exception) . ').' . PHP_EOL); $failed=true;
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if (is_string($directory) && is_dir($directory)) {
        $resolved=realpath($directory); $base=realpath($root . '/storage/testing');
        if ($resolved && $base && !is_link($directory) && str_starts_with($resolved,$base . DIRECTORY_SEPARATOR)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                if ($file->isLink()) { continue; }
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($resolved);
        }
    }
}
exit($failed ? 1 : 0);
