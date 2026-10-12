<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/stage3_database_preflight.php';

$mode = $argv[1] ?? '';
$privateRoot = dirname(__DIR__) . '/storage/testing/stage3-mariadb';
$runPath = $privateRoot . '/auth-http-run.json';

function auth_http_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
    echo '[OK] ' . $label . PHP_EOL;
}

function auth_http_request(array $run, string $path, array $payload = [], ?string $replay = null): array
{
    $handle = curl_init($run['url'] . $path);
    $headers = [];
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
            return strlen($line);
        },
    ]);
    if ($replay === null) {
        curl_setopt($handle, CURLOPT_COOKIEFILE, $run['jar']);
        curl_setopt($handle, CURLOPT_COOKIEJAR, $run['jar']);
    } else {
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['Cookie: PHPSESSID=' . $replay]);
    }
    if ($payload) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($payload));
    }
    $body = curl_exec($handle);
    if ($body === false) {
        throw new RuntimeException('HTTP test transport failed.');
    }
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    $id = null;
    foreach ($headers['set-cookie'] ?? [] as $cookie) {
        if (preg_match('/^PHPSESSID=([^;]*)/', $cookie, $match)) {
            $id = rawurldecode($match[1]);
        }
    }
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $token);
    return ['status' => $status, 'body' => $body, 'headers' => $headers, 'id' => $id, 'token' => $token[1] ?? '', 'location' => $headers['location'][0] ?? ''];
}

function auth_http_login(array $run, string $username, string $password): array
{
    $form = auth_http_request($run, '/admin/login.php');
    if ($form['status'] !== 200 || $form['token'] === '') {
        throw new RuntimeException('Cannot obtain isolated login form.');
    }
    return auth_http_request($run, '/admin/login.php', [
        'username' => $username, 'password' => $password, 'csrf_token' => $form['token'],
        'role' => 'superadmin', 'admin_role' => 'superadmin', 'admin_id' => 1,
    ]);
}

function auth_http_cookie_id(array $run): ?string
{
    foreach (file($run['jar'], FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $fields = explode("\t", $line);
        if (count($fields) === 7 && $fields[5] === 'PHPSESSID') {
            return $fields[6];
        }
    }
    return null;
}

function auth_http_logout(array $run): array
{
    $form = auth_http_request($run, '/admin/change_password.php');
    if ($form['status'] !== 200 || $form['token'] === '') {
        throw new RuntimeException('Cannot obtain authenticated logout token.');
    }
    return auth_http_request($run, '/admin/logout.php', ['csrf_token' => $form['token']]);
}

$checks = 0;
try {
    $pdo = stage3_test_database();
    if ($mode === '--prepare') {
        if (is_file($runPath)) {
            $previous = json_decode(file_get_contents($runPath), true, 512, JSON_THROW_ON_ERROR);
            $previousTag = $previous['tag'] ?? '';
            $previousSnapshot = $privateRoot . '/http-' . $previousTag;
            if (!preg_match('/^auth3_[a-f0-9]{12}$/', $previousTag)
                || realpath($previous['snapshot'] ?? '') !== realpath($previousSnapshot)
                || is_link($previousSnapshot) || !is_file($previousSnapshot . '/result.json')) {
                throw new RuntimeException('Previous authentication snapshot is not verified.');
            }
            $previousResult = json_decode(file_get_contents($previousSnapshot . '/result.json'), true, 512, JSON_THROW_ON_ERROR);
            if (($previousResult['passed'] ?? false) !== true || ($previousResult['fixtures_cleaned'] ?? false) !== true) {
                throw new RuntimeException('Previous authentication run must pass and clean its fixtures before reuse.');
            }
            foreach (['admins', 'admin_activity_logs'] as $table) {
                if ((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() !== 0) {
                    throw new RuntimeException('Existing account/log rows: stop before preparing another authentication run.');
                }
            }
            if (!copy($runPath, $previousSnapshot . '/manifest.json') || !unlink($runPath)) {
                throw new RuntimeException('Could not archive the completed authentication manifest.');
            }
        }
        $port = 8087;
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 1);
        if (is_resource($socket)) {
            fclose($socket);
            throw new RuntimeException('Isolated HTTP port occupied.');
        }
        $existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach (require __DIR__ . '/fixtures/stage3_auth_tables.php' as $table => $ddl) {
            if (!in_array($table, $existingTables, true)) {
                $pdo->exec($ddl);
            }
        }
        $tag = 'auth3_' . bin2hex(random_bytes(6));
        $snapshot = $privateRoot . '/http-' . $tag;
        foreach (['admin','includes','config','templates','storage/sessions'] as $folder) {
            mkdir($snapshot . '/' . $folder, 0700, true);
        }
        foreach (['includes','config','templates'] as $folder) {
            foreach (glob(dirname(__DIR__) . '/' . $folder . '/*.php') as $file) {
                copy($file, $snapshot . '/' . $folder . '/' . basename($file));
            }
        }
        foreach (['login.php','logout.php','change_password.php','activity_logs.php'] as $name) {
            copy(dirname(__DIR__) . '/admin/' . $name, $snapshot . '/admin/' . $name);
        }
        $preflight = var_export(__DIR__ . '/stage3_database_preflight.php', true);
        $manifest = var_export($privateRoot . '/connection.json', true);
        file_put_contents($snapshot . '/config/database.php', '<?php require_once ' . $preflight . '; stage3_test_database(); $c = json_decode(file_get_contents(' . $manifest . '), true, 512, JSON_THROW_ON_ERROR); return $c + ["charset" => "utf8mb4"];');
        $url = 'http://127.0.0.1:' . $port;
        file_put_contents($snapshot . '/config/app.php', '<?php return ' . var_export(['app_name'=>'Synthetic Stage 3', 'environment'=>'local', 'base_url'=>$url], true) . ';');
        $router = '<?php if (PHP_SAPI !== "cli-server") { http_response_code(403); exit; } define("STAGE3_ISOLATED_HTTP", true); require_once __DIR__ . "/includes/error_handling.php"; configure_web_error_handling(); require_once ' . $preflight . '; stage3_test_database(); $p = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); if (!in_array($p, ["/admin/login.php","/admin/logout.php","/admin/change_password.php","/admin/activity_logs.php"], true)) { http_response_code(403); exit("Akses ditolak."); } require __DIR__ . $p;';
        file_put_contents($snapshot . '/router.php', $router);
        $run = ['tag'=>$tag, 'snapshot'=>$snapshot, 'url'=>$url, 'port'=>$port, 'jar'=>$snapshot . '/cookies.txt', 'password'=>bin2hex(random_bytes(24)), 'accounts'=>[]];
        $insert = $pdo->prepare('INSERT INTO admins (username, full_name, role, is_active, password_hash) VALUES (:username, :name, :role, :active, :hash)');
        foreach (['superadmin','editor','ppdb','inactive','invalid'] as $kind) {
            $role = in_array($kind, ['inactive','invalid'], true) ? 'editor' : $kind;
            $username = $tag . '_' . $kind;
            $insert->execute(['username'=>$username, 'name'=>'Synthetic authentication fixture', 'role'=>$role, 'active'=>$kind === 'inactive' ? 0 : 1, 'hash'=>password_hash($run['password'], PASSWORD_DEFAULT)]);
            $run['accounts'][$kind] = ['id'=>(int) $pdo->lastInsertId(), 'username'=>$username];
        }
        // ENUM rejects unknown strings in strict mode; simulate a legacy index-zero role on this fixture only.
        $sqlMode = $pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
        try {
            $pdo->exec("SET SESSION sql_mode = ''");
            $invalid = $pdo->prepare("UPDATE admins SET role = '' WHERE id = :id");
            $invalid->execute(['id'=>$run['accounts']['invalid']['id']]);
        } finally {
            $pdo->exec('SET SESSION sql_mode = ' . $pdo->quote($sqlMode));
        }
        file_put_contents($runPath, json_encode($run, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        file_put_contents($snapshot . '/manifest.json', json_encode($run, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo "Prepared synthetic accounts and isolated HTTP snapshot on port 8087. No application seed imported.\n";
    } elseif ($mode === '--run') {
        $run = json_decode((string) file_get_contents($runPath), true, 512, JSON_THROW_ON_ERROR);
        if ($run['url'] !== 'http://127.0.0.1:8087' || !str_starts_with($run['snapshot'], $privateRoot . '/http-auth3_')) {
            throw new RuntimeException('Unexpected HTTP run target.');
        }
        $preflight = var_export(__DIR__ . '/stage3_database_preflight.php', true);
        $manifest = var_export($privateRoot . '/connection.json', true);
        file_put_contents($run['snapshot'] . '/config/database.php', '<?php require_once ' . $preflight . '; stage3_test_database(); $c = json_decode(file_get_contents(' . $manifest . '), true, 512, JSON_THROW_ON_ERROR); return $c + ["charset" => "utf8mb4"];');
        $matchingSources = true;
        foreach (['includes/auth.php','includes/bootstrap.php','admin/login.php','admin/logout.php','admin/change_password.php','templates/admin_header.php'] as $source) {
            $matchingSources = $matchingSources && hash_file('sha256', dirname(__DIR__) . '/' . $source) === hash_file('sha256', $run['snapshot'] . '/' . $source);
        }
        auth_http_assert($matchingSources, 'HTTP snapshot uses unchanged authentication handlers and templates');
        $reset = $pdo->prepare('UPDATE admins SET is_active = 1 WHERE id = :id AND username = :username');
        $reset->execute($run['accounts']['editor']);
        foreach (['wrong-password','missing','inactive','invalid'] as $case) {
            $username = $case === 'missing' ? $run['tag'] . '_missing' : $run['accounts'][$case === 'wrong-password' ? 'editor' : $case]['username'];
            $result = auth_http_login($run, $username, $case === 'wrong-password' ? 'incorrect-password' : $run['password']);
            auth_http_assert($result['status'] === 200 && str_contains($result['body'], 'Username atau password tidak sesuai.'), $case . ' rejected with generic message');
            $direct = auth_http_request($run, '/admin/change_password.php');
            auth_http_assert($direct['status'] === 302 && str_ends_with($direct['location'], '/admin/login.php'), $case . ' cannot access direct admin URL');
        }
        foreach (['superadmin','editor','ppdb'] as $role) {
            $before = auth_http_request($run, '/admin/login.php');
            $anonymousId = auth_http_cookie_id($run);
            $result = auth_http_login($run, $run['accounts'][$role]['username'], $run['password']);
            $oldId = $result['id'];
            auth_http_assert($result['status'] === 302 && str_ends_with($result['location'], '/admin/dashboard.php'), $role . ' correct credentials accepted');
            auth_http_assert(is_string($oldId) && $oldId !== '' && is_string($anonymousId) && $anonymousId !== $oldId
                && !is_file($run['snapshot'] . '/storage/sessions/sess_' . $anonymousId), $role . ' regenerates ID and deletes anonymous session');
            $direct = auth_http_request($run, '/admin/change_password.php');
            auth_http_assert($direct['status'] === 200 && str_contains($direct['body'], 'Ganti Password Admin'), $role . ' authorized direct page renders');
            $sensitive = auth_http_request($run, '/admin/activity_logs.php');
            auth_http_assert($sensitive['status'] === ($role === 'superadmin' ? 200 : 403), $role . ' sensitive permission enforced despite forged login role');
            $logout = auth_http_logout($run);
            auth_http_assert($logout['status'] === 302 && str_ends_with($logout['location'], '/admin/login.php'), $role . ' logout redirects');
            $cookies = implode('; ', $logout['headers']['set-cookie'] ?? []);
            auth_http_assert(str_contains($cookies, 'PHPSESSID=deleted') && str_contains($cookies, 'Max-Age=0'), $role . ' logout expires session cookie');
            auth_http_assert(!is_file($run['snapshot'] . '/storage/sessions/sess_' . $oldId), $role . ' authenticated session file destroyed');
            $after = auth_http_request($run, '/admin/change_password.php');
            auth_http_assert($after['status'] === 302 && str_ends_with($after['location'], '/admin/login.php'), $role . ' direct URL denied after logout');
            $replay = auth_http_request($run, '/admin/change_password.php', [], $oldId);
            auth_http_assert($replay['status'] === 302 && str_ends_with($replay['location'], '/admin/login.php') && $replay['id'] !== $oldId, $role . ' replayed old cookie denied and replaced');
            $sensitiveReplay = auth_http_request($run, '/admin/activity_logs.php', [], $oldId);
            auth_http_assert($sensitiveReplay['status'] === 302 && str_ends_with($sensitiveReplay['location'], '/admin/login.php'), $role . ' old cookie cannot open sensitive direct URL after logout');
        }
        $result = auth_http_login($run, $run['accounts']['editor']['username'], $run['password']);
        auth_http_assert($result['status'] === 302, 'Editor login before live deactivation');
        $disable = $pdo->prepare('UPDATE admins SET is_active = 0 WHERE id = :id');
        $disable->execute(['id'=>$run['accounts']['editor']['id']]);
        $revoked = auth_http_request($run, '/admin/change_password.php');
        auth_http_assert($revoked['status'] === 302 && str_ends_with($revoked['location'], '/admin/login.php'), 'Database deactivation revokes existing HTTP session');
        $invalidRole = $pdo->prepare('SELECT role FROM admins WHERE id = :id');
        $invalidRole->execute(['id'=>$run['accounts']['invalid']['id']]);
        auth_http_assert($invalidRole->fetchColumn() === '', 'Invalid-role fixture remained invalid without schema change');
        $inactive = $pdo->prepare('SELECT is_active FROM admins WHERE id = :id');
        $inactive->execute(['id'=>$run['accounts']['inactive']['id']]);
        auth_http_assert((int) $inactive->fetchColumn() === 0, 'Nonactive database account was not automatically reactivated');
        $sessionCount = $pdo->prepare('SELECT COUNT(*) FROM admin_activity_logs WHERE username LIKE :prefix AND action = :action');
        foreach (['login','logout','login_failed'] as $action) {
            $sessionCount->execute(['prefix'=>$run['tag'] . '%', 'action'=>$action]);
            auth_http_assert((int) $sessionCount->fetchColumn() > 0, 'Real database recorded ' . $action . ' activity');
        }
        auth_http_assert((int) $pdo->query('SELECT COUNT(*) FROM ppdb_applications')->fetchColumn() === 0, 'No PPDB registration data created');
        file_put_contents($run['snapshot'] . '/result.json', json_encode(['checks'=>$checks, 'passed'=>true, 'date'=>gmdate('c')], JSON_THROW_ON_ERROR));
        echo 'Passed HTTP/database checks: ' . $checks . PHP_EOL;
    } elseif ($mode === '--cleanup') {
        $run = json_decode((string) file_get_contents($runPath), true, 512, JSON_THROW_ON_ERROR);
        $result = json_decode((string) file_get_contents($run['snapshot'] . '/result.json'), true, 512, JSON_THROW_ON_ERROR);
        if (($result['passed'] ?? false) !== true || !preg_match('/^auth3_[a-f0-9]{12}$/', $run['tag'])) {
            throw new RuntimeException('Refusing cleanup without verified successful run.');
        }
        $deleteAccount = $pdo->prepare('DELETE FROM admins WHERE id = :id AND username = :username');
        $deleteLog = $pdo->prepare('DELETE FROM admin_activity_logs WHERE username = :username');
        foreach ($run['accounts'] as $account) {
            if (!str_starts_with($account['username'], $run['tag'] . '_')) {
                throw new RuntimeException('Refusing unrelated fixture cleanup.');
            }
            $deleteAccount->execute($account);
            $deleteLog->execute(['username'=>$account['username']]);
        }
        $deleteLog->execute(['username'=>$run['tag'] . '_missing']);
        $result['fixtures_cleaned'] = true;
        file_put_contents($run['snapshot'] . '/result.json', json_encode($result, JSON_THROW_ON_ERROR));
        echo "Cleaned only this run's synthetic accounts and activity logs. Test tables and private result retained.\n";
    } else {
        throw new RuntimeException('Use --prepare, --run or --cleanup.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . ($exception instanceof PDOException ? 'Testing database operation failed; no fallback attempted.' : $exception->getMessage()) . PHP_EOL);
    exit(1);
}
