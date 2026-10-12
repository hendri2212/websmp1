<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/stage3_database_preflight.php';

$checks = [];
$process = null;
$pdo = null;
$run = null;
$ownedAccounts = [];
$ownedSettings = [];
$ownedInformation = [];
$createdInformationTable = false;
$tables = require __DIR__ . '/fixtures/stage3_content_tables.php';
$cases = require __DIR__ . '/fixtures/stage3_content_cases.php';
$mode = $argv[1] ?? '';

function content_check(bool $condition, string $label): void
{
    global $checks;
    $checks[] = ['passed'=>$condition, 'label'=>$label];
    echo ($condition ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
}

function content_require(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function content_http(array $run, string $path, ?array $payload = null, bool $pdf = false, string $method = ''): array
{
    $handle = curl_init($run['url'] . $path);
    $headers = [];
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PATH_AS_IS=>true,
        CURLOPT_TIMEOUT=>20, CURLOPT_COOKIEFILE=>$run['jar'], CURLOPT_COOKIEJAR=>$run['jar'],
        CURLOPT_HEADERFUNCTION=>static function ($handle, string $line) use (&$headers): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
            return strlen($line);
        }]);
    if ($payload !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        if ($pdf) {
            $payload['document_file'] = new CURLFile($run['snapshot'] . '/fixture.pdf', 'application/pdf', 'TEST_TAHAP3_DOKUMEN.pdf');
        }
        $multipart = $pdf || count(array_filter($payload, static fn($value)=>$value instanceof CURLFile)) > 0;
        curl_setopt($handle, CURLOPT_POSTFIELDS, $multipart ? $payload : http_build_query($payload));
    }
    if ($method !== '') {
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        if ($method === 'HEAD') {
            curl_setopt($handle, CURLOPT_NOBODY, true);
        }
    }
    $body = curl_exec($handle);
    if (!is_string($body)) {
        $transportError = curl_error($handle);
        $transportCode = curl_errno($handle);
        curl_close($handle);
        throw new RuntimeException('Isolated HTTP transport failed (cURL ' . $transportCode . '): ' . $transportError . ' [' . $path . ']');
    }
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    // Upload/delete happens in another process; do not observe cached file metadata.
    clearstatcache();
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $token);
    return ['status'=>$status, 'body'=>$body, 'token'=>$token[1] ?? '', 'headers'=>$headers];
}

function content_token(array $run, string $path): string
{
    $page = content_http($run, $path);
    content_require($page['status'] === 200 && $page['token'] !== '', 'Protected form unavailable: ' . $path);
    return $page['token'];
}

function content_row(PDO $pdo, array $case, string $value): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM `' . $case['table'] . '` WHERE `' . $case['field'] . '` = :value');
    $stmt->execute(['value'=>$value]);
    $rows = $stmt->fetchAll();
    content_require(count($rows) <= 1, 'Duplicate synthetic identifier.');
    return $rows[0] ?? null;
}

function content_state(PDO $pdo, string $table, array $run): string
{
    $files = [];
    foreach (['storage/uploads','public/uploads'] as $folder) {
        $dir = $run['snapshot'] . '/' . $folder;
        if (!is_dir($dir)) {
            continue;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
    }
    ksort($files);
    return hash('sha256', json_encode([$pdo->query('SELECT * FROM `' . $table . '` ORDER BY id')->fetchAll(), $files], JSON_THROW_ON_ERROR));
}

function content_submit(array $run, string $path, array $payload, bool $pdf = false): array
{
    $payload['csrf_token'] = content_token($run, $path);
    return content_http($run, $path, $payload, $pdf);
}

function content_rejects(PDO $pdo, array $run, array $anonymous, array $blocked, string $path, string $table, array $payload, string $label, bool $pdf = false): void
{
    $before = content_state($pdo, $table, $run);
    $reply = content_http($anonymous, $path, $payload, $pdf);
    content_check($reply['status'] === 302 && content_state($pdo, $table, $run) === $before, $label . ': anonymous denied without mutation');
    $blockedPayload = $payload + ['csrf_token'=>content_token($blocked, '/admin/change_password.php'), 'role'=>'superadmin'];
    $reply = content_http($blocked, $path, $blockedPayload, $pdf);
    content_check($reply['status'] === 403 && content_state($pdo, $table, $run) === $before, $label . ': forbidden role denied despite valid CSRF');
    foreach ([null, 'invalid-csrf-token'] as $token) {
        unset($payload['csrf_token']);
        if ($token !== null) {
            $payload['csrf_token'] = $token;
        }
        $reply = content_http($run, $path, $payload, $pdf);
        content_check($reply['status'] === 200 && str_contains($reply['body'], 'Sesi form sudah tidak valid')
            && content_state($pdo, $table, $run) === $before, $label . ': ' . ($token === null ? 'missing' : 'invalid') . ' CSRF prevents mutation');
    }
}

function content_prepare_tables(PDO $pdo, array $tables): void
{
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table => $columns) {
        content_require(!str_starts_with($table, 'ppdb'), 'PPDB DDL forbidden.');
        $parts = [];
        foreach ($columns as $name => $definition) {
            $parts[] = '`' . $name . '` ' . $definition;
        }
        if (!in_array($table, $existing, true)) {
            $pdo->exec('CREATE TABLE `' . $table . '` (' . implode(', ', $parts) . ')');
            continue;
        }
        content_require((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0, 'Existing content rows: stop before fixture migration.');
        $actual = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        content_require(!array_diff($actual, array_keys($columns)), 'Unexpected fixture columns: manual review required.');
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $actual, true)) {
                $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $name . '` ' . $definition);
            }
        }
    }
}

function content_payload(array $case, string $value): array
{
    $payload = $case['payload'];
    $payload[$case['field']] = $value;
    if ($case['table'] === 'news') {
        $payload['slug'] = strtolower($value);
    }
    return $payload;
}

try {
    content_require(in_array($mode, ['--run','--roles','--backup','--uploads','--downloads','--inputs','--consistency','--errors','--ids','--validation','--announcements','--ppdb-info'], true), 'Use a supported explicit test mode; only the verified isolated database is allowed.');
    $pdo = stage3_test_database();
    foreach (['admins','site_settings','admin_activity_logs','ppdb_applications'] as $table) {
        content_require((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0, 'Existing non-fixture data: stop before test.');
    }
    $ppdbBefore = $pdo->query('SHOW CREATE TABLE ppdb_applications')->fetch()['Create Table'];
    if ($mode === '--backup') {
        // Completeness testing must not provision or migrate even the isolated database.
        foreach ($tables as $table => $columns) {
            $actual = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
            content_require(!array_diff(array_keys($columns), $actual), 'Backup fixture schema incomplete; stop without DDL.');
            content_require((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0, 'Existing content: stop backup fixture testing.');
        }
    } else {
        content_prepare_tables($pdo, $tables);
    }
    if ($mode === '--ppdb-info') {
        $createdInformationTable = !in_array('ppdb_entries', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true);
        if ($createdInformationTable) {
            $pdo->exec('CREATE TABLE ppdb_entries (id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
        }
        content_require((int) $pdo->query('SELECT COUNT(*) FROM ppdb_entries')->fetchColumn() === 0, 'Existing information data: stop before testing.');
    }
    $privateRoot = dirname(__DIR__) . '/storage/testing/stage3-mariadb';
    $prefix = ['--run'=>'crud3_', '--roles'=>'role3_', '--backup'=>'crud3_', '--uploads'=>'upload3_', '--downloads'=>'download3_', '--inputs'=>'input3_', '--consistency'=>'consistency3_', '--errors'=>'error3_', '--ids'=>'input3_', '--validation'=>'input3_', '--announcements'=>'crud3_','--ppdb-info'=>'crud3_'][$mode];
    $tag = $prefix . bin2hex(random_bytes(6));
    $snapshot = $privateRoot . '/http-' . $tag;
    $socket = @stream_socket_client('tcp://127.0.0.1:8088', $code, $message, 1);
    if (is_resource($socket)) {
        fclose($socket);
        throw new RuntimeException('Testing port 8088 occupied.');
    }
    foreach (['admin','includes','config','templates','pages','storage/sessions','storage/tmp','public/uploads'] as $folder) {
        mkdir($snapshot . '/' . $folder, 0700, true);
    }
    $sources = ['index.php'];
    if ($mode === '--backup') {
        mkdir($snapshot . '/database', 0700, true);
        $sources[] = 'database/schema.sql';
    }
    if (in_array($mode, ['--uploads','--consistency','--ppdb-info'], true)) {
        $sources = array_merge($sources, ['preview_router.php','.htaccess','public/uploads/.htaccess','storage/.htaccess']);
    }
    if ($mode === '--ppdb-info') {
        mkdir($snapshot . '/public/assets/css', 0700, true);
        $sources = array_merge($sources, ['public/assets/css/style.css','public/logo-sekolah.png','public/favicon.png']);
    }
    foreach (['includes','config','templates','pages'] as $folder) {
        foreach (glob(dirname(__DIR__) . '/' . $folder . '/*.php') as $file) {
            $sources[] = $folder . '/' . basename($file);
        }
    }
    $adminNames = array_merge(array_keys($cases), ['login','logout','change_password','contact_messages','settings']);
    if ($mode === '--ppdb-info') {
        $adminNames = array_merge($adminNames, ['ppdb','ppdb_applications','ppdb_export','ppdb_download','dashboard']);
    }
    if (in_array($mode, ['--roles', '--backup', '--ids', '--validation', '--consistency'], true)) {
        $adminNames = array_merge($adminNames, ['admins','db_backup','activity_logs','production_check','dashboard']);
    }
    foreach ($adminNames as $name) {
        $sources[] = 'admin/' . $name . '.php';
    }
    foreach ($sources as $source) {
        copy(dirname(__DIR__) . '/' . $source, $snapshot . '/' . $source);
    }
    $preflight = var_export(__DIR__ . '/stage3_database_preflight.php', true);
    $manifest = var_export($privateRoot . '/connection.json', true);
    file_put_contents($snapshot . '/config/database.php', '<?php require_once ' . $preflight . '; stage3_test_database(); $c=json_decode(file_get_contents(' . $manifest . '),true,512,JSON_THROW_ON_ERROR); return $c + ["charset"=>"utf8mb4"];');
    file_put_contents($snapshot . '/config/app.php', '<?php return ' . var_export(['app_name'=>'Synthetic Stage 3', 'environment'=>'local', 'base_url'=>'http://127.0.0.1:8088'], true) . ';');
    $adminPaths = array_map(static fn($name)=>'/admin/' . $name . '.php', $adminNames);
    $publicPaths = ['/', '/profil','/kontak','/faq','/berita','/galeri','/dokumen','/dokumen/unduh','/akreditasi/file','/guru-staf','/agenda','/prestasi','/fasilitas','/ekstrakurikuler'];
    if ($mode === '--ppdb-info') {
        $publicPaths = array_merge($publicPaths, ['/ppdb','/ppdb/bukti','/kebijakan-privasi']);
        $adminPaths[] = '/pages/ppdb_receipt.php';
    }
    $staticRoute = in_array($mode, ['--uploads','--consistency'], true) ? 'if(str_starts_with($p,"/public/")){return require __DIR__."/preview_router.php";}' : '';
    if (in_array($mode, ['--inputs', '--ids'], true)) {
        $staticRoute = 'if(preg_match("~^/(berita|prestasi)/[^/]*$~",$p)){require __DIR__."/index.php";exit;}';
    }
    if ($mode === '--errors') {
        copy(__DIR__ . '/fixtures/stage3_error_http_probe.php', $snapshot . '/error-probe.php');
        $staticRoute = 'if($p==="/__stage3_error_probe" && hash_equals(' . var_export($tag, true) . ',(string)($_GET["key"]??""))){require __DIR__."/error-probe.php";exit;} if(preg_match("~^/berita/[a-z0-9_]+$~",$p)){require __DIR__."/index.php";exit;}';
    }
    $router = '<?php if(PHP_SAPI!=="cli-server"){http_response_code(403);exit;} define("STAGE3_ISOLATED_HTTP",true); require_once __DIR__."/includes/error_handling.php"; configure_web_error_handling(); require_once ' . $preflight . '; stage3_test_database(); $p=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH); ' . $staticRoute . ' if(in_array($p,' . var_export($adminPaths, true) . ',true)){require __DIR__.$p;} elseif(in_array($p,' . var_export($publicPaths, true) . ',true)){require __DIR__."/index.php";} else {http_response_code(403);exit("Akses ditolak.");}';
    if ($mode === '--ppdb-info') {
        $router = '<?php if(PHP_SAPI!=="cli-server"){http_response_code(403);exit;} define("STAGE3_ISOLATED_HTTP",true); require_once ' . $preflight . '; stage3_test_database(); return require __DIR__."/preview_router.php";';
    }
    file_put_contents($snapshot . '/router.php', $router);
    $pdf = "%PDF-1.4\n";
    $objects = ["<< /Type /Catalog /Pages 2 0 R >>", "<< /Type /Pages /Kids [3 0 R] /Count 1 >>", "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>"];
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 4\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    file_put_contents($snapshot . '/fixture.pdf', $pdf);
    $run = ['tag'=>$tag,'snapshot'=>$snapshot,'url'=>'http://127.0.0.1:8088','jar'=>$snapshot . '/super.cookies'];
    file_put_contents($snapshot . '/manifest.json', json_encode($run, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    $same = true;
    foreach ($sources as $source) {
        if (!in_array($source, ['config/app.php','config/database.php'], true)) {
            $same = $same && hash_file('sha256', dirname(__DIR__) . '/' . $source) === hash_file('sha256', $snapshot . '/' . $source);
        }
    }
    content_check($same, 'Snapshot handlers/repositories/pages match unchanged project sources');
    $password = bin2hex(random_bytes(24));
    $insert = $pdo->prepare('INSERT INTO admins (username,full_name,role,is_active,password_hash) VALUES (:username,:name,:role,1,:hash)');
    foreach (['superadmin','editor','ppdb'] as $role) {
        $username = $tag . '_' . $role;
        $insert->execute(['username'=>$username,'name'=>'Synthetic CRUD test','role'=>$role,'hash'=>password_hash($password, PASSWORD_DEFAULT)]);
        $ownedAccounts[$role] = ['id'=>(int) $pdo->lastInsertId(), 'username'=>$username];
    }
    $serverCommand = [PHP_BINARY, '-d', 'sys_temp_dir=' . $snapshot . '/storage/tmp'];
    if ($mode === '--errors') {
        $serverCommand = array_merge($serverCommand, ['-d','display_errors=1','-d','display_startup_errors=1']);
    }
    if ($mode === '--uploads') {
        $serverCommand = array_merge($serverCommand, ['-d','upload_max_filesize=24M','-d','post_max_size=32M','-d','memory_limit=256M']);
    }
    $zipExtension = dirname(PHP_BINARY) . '/ext/php_zip.dll';
    // CLI -d flags are not inherited by the HTTP child; inspect its default configuration.
    $childHasZip = false;
    if (in_array($mode, ['--roles', '--backup'], true)) {
        $zipProbe = proc_open([PHP_BINARY, '-r', 'exit(class_exists("ZipArchive") ? 0 : 1);'],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $zipPipes);
        content_require(is_resource($zipProbe), 'Cannot inspect HTTP child ZIP configuration.');
        fclose($zipPipes[0]); stream_get_contents($zipPipes[1]); stream_get_contents($zipPipes[2]);
        fclose($zipPipes[1]); fclose($zipPipes[2]); $zipStatus = proc_close($zipProbe);
        content_require(in_array($zipStatus, [0,1], true), 'HTTP child ZIP inspection failed.');
        $childHasZip = $zipStatus === 0;
    }
    if (in_array($mode, ['--roles', '--backup'], true) && !$childHasZip && is_file($zipExtension)) {
        $serverCommand = array_merge($serverCommand, ['-d', 'extension=' . $zipExtension]);
        $run['zip_runtime_override'] = true;
        file_put_contents($snapshot . '/manifest.json', json_encode($run, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo "Test server loads existing ZIP extension for this process only; php.ini unchanged.\n";
    }
    $serverCommand = array_merge($serverCommand, ['-S', '127.0.0.1:8088', '-t', $snapshot, $snapshot . '/router.php']);
    $process = proc_open($serverCommand,
        [0=>['pipe','r'],1=>['file',$snapshot . '/server.out.log','a'],2=>['file',$snapshot . '/server.err.log','a']], $pipes, $snapshot, null, ['bypass_shell'=>true]);
    content_require(is_resource($process), 'Could not start isolated HTTP server.');
    fclose($pipes[0]);
    $ready = false;
    for ($i=0; $i<40; $i++) {
        $socket = @stream_socket_client('tcp://127.0.0.1:8088', $code, $message, 0.1);
        if (is_resource($socket)) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    content_require($ready, 'Isolated server did not become ready.');
    $clients = [];
    $retiredCsrfTokens = [];
    foreach ($ownedAccounts as $role => $account) {
        $client = array_replace($run, ['jar'=>$snapshot . '/' . $role . '.cookies']);
        $retiredCsrfTokens[$role] = content_token($client, '/admin/login.php');
        $login = content_http($client, '/admin/login.php', ['username'=>$account['username'],'password'=>$password,
            'csrf_token'=>$retiredCsrfTokens[$role]]);
        content_require($login['status'] === 302, 'Synthetic login failed: ' . $role);
        $clients[$role] = $client;
    }
    $run = $clients['superadmin'];
    $anonymous = array_replace($run, ['jar'=>$snapshot . '/anonymous.cookies']);
    $blocked = $clients['ppdb'];

    if ($mode === '--run') {
        require __DIR__ . '/fixtures/stage4_announcement_checks.php';
        stage4_announcement_checks($pdo, $run, $anonymous, $blocked, $cases);
    }

    if ($mode === '--ppdb-info') {
        require __DIR__ . '/fixtures/stage4_ppdb_information_checks.php';
        stage4_ppdb_information_checks($pdo, $run, $clients, $anonymous, $ownedInformation);
        require __DIR__ . '/fixtures/stage4_ppdb_legacy_security_checks.php';
        stage4_ppdb_legacy_security_checks($pdo, $run, $clients, $anonymous, $retiredCsrfTokens);
        require __DIR__ . '/fixtures/stage4_ppdb_private_file_checks.php';
        stage4_ppdb_private_file_checks($pdo, $run, $clients, $anonymous);
        $expected = require $snapshot . '/config/site_data.php';
        file_put_contents($snapshot . '/ppdb-public-expected.json', json_encode($expected, JSON_THROW_ON_ERROR));
        $browserProcess = proc_open(['node', __DIR__ . '/stage4_ppdb_public_browser_test.cjs', $snapshot],
            [1=>['pipe','w'], 2=>['file',$snapshot . '/browser-error.log','a']], $browserPipes, dirname(__DIR__));
        content_require(is_resource($browserProcess), 'Cannot start public browser test.');
        $browserOutput = stream_get_contents($browserPipes[1]);
        fclose($browserPipes[1]);
        $browserExit = proc_close($browserProcess);
        $browserResult = json_decode($browserOutput, true);
        content_check($browserExit === 0 && is_array($browserResult) && $browserResult['failures'] === 0,
            'Public PPDB browser layout/content: ' . trim($browserOutput));
    } elseif ($mode === '--announcements') {
        require __DIR__ . '/fixtures/stage4_announcement_checks.php';
        stage4_announcement_checks($pdo, $run, $anonymous, $blocked, $cases);
    } elseif ($mode === '--validation') {
        require __DIR__ . '/fixtures/stage4_input_validation_checks.php';
        stage4_input_validation_checks($pdo, $run, $anonymous, $cases, $ownedAccounts, $ownedSettings);
    } elseif ($mode === '--ids') {
        require __DIR__ . '/fixtures/stage4_id_checks.php';
        stage4_id_checks($pdo, $run, $anonymous, $cases, $ownedAccounts);
    } elseif ($mode === '--errors') {
        require __DIR__ . '/fixtures/stage3_error_checks.php';
        stage3_error_checks($pdo, $run, $anonymous, $cases, $ownedSettings, $ownedAccounts);
    } elseif ($mode === '--consistency') {
        require __DIR__ . '/fixtures/stage3_upload_checks.php';
        require __DIR__ . '/fixtures/stage3_consistency_checks.php';
        stage3_consistency_checks($pdo, $run, $anonymous, $cases, $ownedSettings);
        require __DIR__ . '/fixtures/stage4_media_lifecycle_checks.php';
        stage4_media_lifecycle_checks($run);
        require __DIR__ . '/fixtures/stage4_delete_feedback_checks.php';
        stage4_delete_feedback_checks($pdo, $run, $clients, $anonymous, $cases, $ownedAccounts, $password);
    } elseif ($mode === '--inputs') {
        require __DIR__ . '/fixtures/stage3_input_checks.php';
        stage3_input_checks($pdo, $run, $anonymous, $cases, $ownedSettings);
    } elseif ($mode === '--downloads') {
        require __DIR__ . '/fixtures/stage3_download_checks.php';
        stage3_download_checks($pdo, $run, $anonymous, $ownedSettings);
    } elseif ($mode === '--uploads') {
        require __DIR__ . '/fixtures/stage3_upload_checks.php';
        stage3_upload_checks($pdo, $run, $clients, $anonymous, $cases, $ownedSettings);
    } elseif ($mode === '--backup') {
        require __DIR__ . '/fixtures/stage5_backup_completeness_checks.php';
        stage5_backup_completeness_checks($pdo, $run, $cases, $ownedSettings);
    } elseif ($mode === '--roles') {
        require __DIR__ . '/fixtures/stage3_crud_role_checks.php';
        stage3_crud_role_checks($pdo, $run, $clients, $anonymous, $cases, $ownedAccounts, $ownedSettings, $password);
    } else {
    foreach ($cases as $module => $case) {
        $path = '/admin/' . $module . '.php';
        $label = 'TEST_TAHAP3_' . $case['label'] . '_' . $tag;
        $primary = content_payload($case, $label);
        $sentinel = content_payload($case, $label . '_PEMBANDING');
        content_rejects($pdo, $run, $anonymous, $blocked, $path, $case['table'], $primary, $module . ' CREATE', $module === 'documents');
        foreach ([$primary,$sentinel] as $payload) {
            $reply = content_submit($run, $path, $payload, $module === 'documents');
            $row = content_row($pdo, $case, $payload[$case['field']]);
            content_check($reply['status'] === 200 && $row !== null, $module . ' CREATE stores exact synthetic record');
        }
        $row = content_row($pdo, $case, $label);
        $guard = content_row($pdo, $case, $label . '_PEMBANDING');
        content_require($row !== null && $guard !== null, 'Cannot continue module without two fixtures: ' . $module);
        $read = content_http($run, $path . '?edit=' . $row['id']);
        content_check($read['status'] === 200 && str_contains($read['body'], 'value="' . $row['id'] . '"') && str_contains($read['body'], $label), $module . ' READ admin list/edit renders stored record');
        $public = content_http($anonymous, $case['public']);
        content_check($public['status'] === 200 && str_contains($public['body'], $label), $module . ' READ public renders stored record');
        if ($module === 'documents') {
            content_check(is_file($snapshot . '/' . $row['file_path']) && hash_file('sha256', $snapshot . '/' . $row['file_path']) === hash('sha256', $pdf), 'documents uploaded PDF persisted in isolated storage');
            $download = content_http($anonymous, '/dokumen/unduh?id=' . $row['id']);
            content_check($download['status'] === 200 && hash('sha256', $download['body']) === hash('sha256', $pdf), 'documents READ download returns exact uploaded PDF');
        }
        $update = content_payload($case, $label . '_EDIT');
        $update['id'] = (string) $row['id'];
        content_rejects($pdo, $run, $anonymous, $blocked, $path, $case['table'], $update, $module . ' UPDATE');
        $reply = content_submit($clients['editor'], $path, $update);
        $edited = content_row($pdo, $case, $label . '_EDIT');
        content_check($reply['status'] === 200 && $edited !== null && (int) $edited['id'] === (int) $row['id']
            && content_row($pdo, $case, $label) === null && content_row($pdo, $case, $label . '_PEMBANDING') === $guard, $module . ' UPDATE editor changes only intended record');
        $reread = content_http($run, $path . '?edit=' . $row['id']);
        $public = content_http($anonymous, $case['public']);
        content_check($reread['status'] === 200 && str_contains($reread['body'], $label . '_EDIT')
            && $public['status'] === 200 && str_contains($public['body'], $label . '_EDIT'), $module . ' UPDATE visible on subsequent admin/public request');
        $delete = ['delete_id'=>(string) $row['id']];
        content_rejects($pdo, $run, $anonymous, $blocked, $path, $case['table'], $delete, $module . ' DELETE');
        $reply = content_submit($run, $path, $delete);
        content_check($reply['status'] === 200 && content_row($pdo, $case, $label . '_EDIT') === null
            && content_row($pdo, $case, $label . '_PEMBANDING') === $guard, $module . ' DELETE removes only intended record');
        $public = content_http($anonymous, $case['public']);
        content_check($public['status'] === 200 && !str_contains($public['body'], $label . '_EDIT'), $module . ' DELETE absent on subsequent public request');
        $before = content_state($pdo, $case['table'], $run);
        $reply = content_http($run, $path . '?delete_id=' . $guard['id']);
        content_check($reply['status'] === 200 && content_state($pdo, $case['table'], $run) === $before, $module . ' GET cannot delete record');
        foreach (['2147483647','-1','not-an-id','0'] as $invalid) {
            $before = content_state($pdo, $case['table'], $run);
            $reply = content_http($run, $path . '?edit=' . rawurlencode($invalid));
            content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, $case['table'], $run) === $before, $module . ' invalid READ ID ' . $invalid . ' handled without mutation');
            $invalidUpdate = content_payload($case, $label . '_INVALID_' . str_replace('-', '_', $invalid));
            $invalidUpdate['id'] = $invalid;
            $reply = content_submit($run, $path, $invalidUpdate);
            content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, $case['table'], $run) === $before, $module . ' invalid UPDATE ID ' . $invalid . ' cannot create/change records');
            $before = content_state($pdo, $case['table'], $run);
            $reply = content_submit($run, $path, ['delete_id'=>$invalid]);
            content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, $case['table'], $run) === $before, $module . ' invalid DELETE ID ' . $invalid . ' cannot delete records');
        }
        $before = content_state($pdo, $case['table'], $run);
        $invalidUpdate = content_payload($case, $label . '_INVALID_SUFFIX');
        $invalidUpdate['id'] = $guard['id'] . 'abc';
        $reply = content_submit($run, $path, $invalidUpdate);
        content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, $case['table'], $run) === $before, $module . ' malformed existing UPDATE ID cannot target valid record');
        $restore = $sentinel;
        $restore['id'] = (string) $guard['id'];
        content_submit($run, $path, $restore);
        $before = content_state($pdo, $case['table'], $run);
        $reply = content_submit($run, $path, ['delete_id'=>$guard['id'] . 'abc']);
        content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, $case['table'], $run) === $before, $module . ' malformed existing DELETE ID cannot target valid record');
    }

    $messageCase = ['table'=>'contact_messages','field'=>'subject'];
    $messageLabel = 'TEST_TAHAP3_PESAN_' . $tag;
    $messagePayload = ['name'=>'Pengunjung sintetis','email'=>'synthetic@example.invalid','phone'=>'', 'subject'=>$messageLabel,'message'=>'Pesan sintetis bukan data sekolah'];
    $before = content_state($pdo, 'contact_messages', $run);
    $reply = content_http($anonymous, '/kontak', $messagePayload + ['csrf_token'=>'invalid']);
    content_check($reply['status'] === 200 && content_state($pdo, 'contact_messages', $run) === $before, 'messages public CREATE invalid CSRF prevents mutation');
    foreach ([$messageLabel, $messageLabel . '_PEMBANDING'] as $label) {
        $reply = content_submit($anonymous, '/kontak', array_replace($messagePayload, ['subject'=>$label]));
        content_check($reply['status'] === 200 && content_row($pdo, $messageCase, $label) !== null, 'messages CREATE public form stores synthetic record');
    }
    $messageRow = content_row($pdo, $messageCase, $messageLabel);
    $messageGuard = content_row($pdo, $messageCase, $messageLabel . '_PEMBANDING');
    content_require($messageRow !== null && $messageGuard !== null, 'Message fixtures unavailable.');
    $read = content_http($run, '/admin/contact_messages.php');
    content_check($read['status'] === 200 && str_contains($read['body'], $messageLabel), 'messages READ admin renders incoming message');
    $statusPayload = ['message_id'=>(string) $messageRow['id'],'status'=>'diproses'];
    content_rejects($pdo, $run, $anonymous, $blocked, '/admin/contact_messages.php', 'contact_messages', $statusPayload, 'messages UPDATE');
    $reply = content_submit($clients['editor'], '/admin/contact_messages.php', $statusPayload);
    content_check($reply['status'] === 200 && content_row($pdo, $messageCase, $messageLabel)['status'] === 'diproses'
        && content_row($pdo, $messageCase, $messageLabel . '_PEMBANDING') === $messageGuard, 'messages UPDATE status changes only intended record');
    $before = content_state($pdo, 'contact_messages', $run);
    content_submit($run, '/admin/contact_messages.php', array_replace($statusPayload, ['status'=>'invalid']));
    content_check(content_state($pdo, 'contact_messages', $run) === $before, 'messages invalid status cannot change record');
    foreach (['2147483647','-1','not-an-id','0', $messageGuard['id'] . 'abc'] as $invalid) {
        $before = content_state($pdo, 'contact_messages', $run);
        $reply = content_submit($run, '/admin/contact_messages.php', ['message_id'=>$invalid,'status'=>'selesai']);
        content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, 'contact_messages', $run) === $before, 'messages invalid UPDATE ID ' . $invalid . ' cannot change record');
        content_submit($run, '/admin/contact_messages.php', ['message_id'=>(string) $messageGuard['id'],'status'=>'baru']);
        $before = content_state($pdo, 'contact_messages', $run);
        $reply = content_submit($run, '/admin/contact_messages.php', ['delete_id'=>$invalid]);
        content_check(in_array($reply['status'], [200,400,404], true) && content_state($pdo, 'contact_messages', $run) === $before, 'messages invalid DELETE ID ' . $invalid . ' cannot delete record');
    }
    if (content_row($pdo, $messageCase, $messageLabel . '_PEMBANDING') === null) {
        content_submit($anonymous, '/kontak', array_replace($messagePayload, ['subject'=>$messageLabel . '_PEMBANDING']));
    }
    $messageGuard = content_row($pdo, $messageCase, $messageLabel . '_PEMBANDING');
    content_rejects($pdo, $run, $anonymous, $blocked, '/admin/contact_messages.php', 'contact_messages', ['delete_id'=>(string) $messageRow['id']], 'messages DELETE');
    $reply = content_submit($run, '/admin/contact_messages.php', ['delete_id'=>(string) $messageRow['id']]);
    content_check($reply['status'] === 200 && content_row($pdo, $messageCase, $messageLabel) === null
        && content_row($pdo, $messageCase, $messageLabel . '_PEMBANDING') === $messageGuard, 'messages DELETE removes only intended record');

    $settingsPayload = ['name'=>'TEST_TAHAP3_PROFIL_' . $tag,'history_text'=>'TEST_TAHAP3_SEJARAH_' . $tag,
        'vision_text'=>'Visi sintetis','mission_text'=>'Misi sintetis','home_hero_title'=>'TEST_TAHAP3_HOME_' . $tag,
        'current_academic_year'=>'2025/2026','home_show_announcements'=>'1'];
    content_rejects($pdo, $run, $anonymous, $clients['editor'], '/admin/settings.php', 'site_settings', $settingsPayload, 'settings CREATE/UPDATE');
    $reply = content_submit($run, '/admin/settings.php', $settingsPayload);
    $ownedSettings = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
    $profileStmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key');
    $profileStmt->execute(['key'=>'schoolProfile']);
    $profile = json_decode((string) $profileStmt->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
    content_check($reply['status'] === 200 && $profile['name'] === $settingsPayload['name'], 'settings CREATE persists profile JSON through admin form');
    $public = content_http($anonymous, '/profil');
    $read = content_http($run, '/admin/settings.php');
    content_check($public['status'] === 200 && str_contains($public['body'], $settingsPayload['history_text'])
        && $read['status'] === 200 && str_contains($read['body'], $settingsPayload['name']), 'settings READ persisted profile in admin/public page');
    $keysBefore = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
    $settingsPayload['name'] .= '_EDIT';
    $settingsPayload['history_text'] .= '_EDIT';
    $settingsPayload['home_hero_title'] .= '_EDIT';
    content_rejects($pdo, $run, $anonymous, $clients['editor'], '/admin/settings.php', 'site_settings', $settingsPayload, 'settings UPDATE existing');
    $reply = content_submit($run, '/admin/settings.php', $settingsPayload);
    $profileStmt->execute(['key'=>'schoolProfile']);
    $profile = json_decode((string) $profileStmt->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
    content_check($reply['status'] === 200 && $profile['name'] === $settingsPayload['name']
        && $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll() === $keysBefore, 'settings UPDATE changes existing values without duplicate setting records');
    $public = content_http($anonymous, '/profil');
    $home = content_http($anonymous, '/');
    content_check($public['status'] === 200 && str_contains($public['body'], $settingsPayload['history_text'])
        && $home['status'] === 200 && str_contains($home['body'], $settingsPayload['home_hero_title']), 'settings UPDATE profile/home visible on subsequent requests');
    }
    content_check($pdo->query('SHOW CREATE TABLE ppdb_applications')->fetch()['Create Table'] === $ppdbBefore
        && (int) $pdo->query('SELECT COUNT(*) FROM ppdb_applications')->fetchColumn() === 0, 'PPDB dependency schema and records unchanged');
} catch (Throwable $exception) {
    content_check(false, 'Runner stopped: ' . ($exception instanceof PDOException ? 'isolated database operation failed' : $exception->getMessage()));
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    if ($pdo instanceof PDO && $run !== null) {
        try {
            foreach ($ownedInformation as $record) {
                $statement = $pdo->prepare('DELETE FROM ppdb_entries WHERE id = :id AND title = :title');
                $statement->execute($record);
            }
            if ($mode === '--ppdb-info') {
                content_check((int) $pdo->query('SELECT COUNT(*) FROM ppdb_entries')->fetchColumn() === 0, 'Information fixtures cleaned without applicant data');
            }
            // All content tables were verified empty initially; remove only exact tagged fixture values.
            foreach ($cases + ['contact_messages'=>['table'=>'contact_messages','field'=>'subject']] as $case) {
                $stmt = $pdo->prepare('SELECT id, `' . $case['field'] . '` AS fixture FROM `' . $case['table'] . '`');
                $stmt->execute();
                $delete = $pdo->prepare('DELETE FROM `' . $case['table'] . '` WHERE id = :id AND `' . $case['field'] . '` = :fixture');
                foreach ($stmt->fetchAll() as $row) {
                    if (str_starts_with($row['fixture'], 'TEST_TAHAP3_') && str_contains($row['fixture'], $run['tag'])) {
                        $delete->execute($row);
                    }
                }
            }
            // Keep the exact setting IDs from this run; no global cleanup by key or prefix.
            $deleteSetting = $pdo->prepare('DELETE FROM site_settings WHERE id = :id AND setting_key = :setting_key');
            foreach ($ownedSettings as $setting) {
                $deleteSetting->execute($setting);
            }
            $deleteAccount = $pdo->prepare('DELETE FROM admins WHERE id = :id AND username = :username');
            $deleteLog = $pdo->prepare('DELETE FROM admin_activity_logs WHERE username = :username');
            foreach ($ownedAccounts as $account) {
                $deleteAccount->execute($account);
                $deleteLog->execute(['username'=>$account['username']]);
            }
            $remaining = [];
            foreach (array_merge(array_keys($tables), ['admins','site_settings','admin_activity_logs']) as $table) {
                $remaining[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            }
            content_check(array_sum($remaining) === 0, 'Cleanup leaves no synthetic content/accounts/settings/activity rows');
        } catch (Throwable $exception) {
            content_check(false, 'Fixture cleanup failed; inspect isolated run before reusing database.');
        }
        $summary = ['date'=>gmdate('c'),'checks'=>count($checks),'passed'=>count(array_filter($checks, static fn($check)=>$check['passed'])),
            'failed'=>array_values(array_filter($checks, static fn($check)=>!$check['passed'])), 'details'=>$checks];
        file_put_contents($run['snapshot'] . '/result.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'RESULT: ' . $summary['passed'] . '/' . $summary['checks'] . ' passed; failures=' . count($summary['failed']) . PHP_EOL;
        echo 'Private evidence directory: ' . $run['snapshot'] . PHP_EOL;
    }
}
exit(count(array_filter($checks, static fn($check)=>!$check['passed'])) ? 1 : 0);
