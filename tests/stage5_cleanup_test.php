<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (!in_array($argv[1] ?? '', ['--plan','--apply'], true)) { fwrite(STDERR, "Use --plan or --apply.\n"); exit(1); }
require __DIR__ . '/stage3_database_preflight.php';

function cleanup5_assert(bool $condition): void
{
    if (!$condition) { throw new RuntimeException('Testing cleanup ownership or safety check failed.'); }
}

function cleanup5_within(string $path, string $root): bool
{
    $resolved = realpath($path); $base = realpath($root);
    return is_string($resolved) && is_string($base) && !is_link($path)
        && str_starts_with(strtolower($resolved), strtolower(rtrim($base, '/\\') . DIRECTORY_SEPARATOR));
}

function cleanup5_original_state(): string
{
    $root = dirname(__DIR__); $state = [];
    foreach (['config','database','templates','admin','includes','pages','public','storage/uploads','storage/backups'] as $folder) {
        if (!is_dir($root . '/' . $folder)) { continue; }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $folder, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) { $state[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
        }
    }
    ksort($state);
    return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
}

try {
    $mode = $argv[1];
    $pdo = stage3_test_database();
    $private = realpath(dirname(__DIR__) . '/storage/testing/stage3-mariadb');
    cleanup5_assert(is_string($private));
    foreach ([8087,8088,8089,8091] as $port) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.2);
        if (is_resource($socket)) { fclose($socket); throw new RuntimeException('Testing server still listening; refuse cleanup.'); }
    }
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        cleanup5_assert(preg_match('/\A[a-zA-Z0-9_]+\z/', $table) === 1);
        if ($table !== 'stage3_test_environment') { cleanup5_assert((int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() === 0); }
    }
    $before = cleanup5_original_state();
    $config = json_decode(file_get_contents($private . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
    $adminConfig = json_decode(file_get_contents($private . '/bootstrap-admin.json'), true, 512, JSON_THROW_ON_ERROR);
    $admin = new PDO('mysql:host=127.0.0.1;port=33317;charset=utf8mb4', $adminConfig['username'], $adminConfig['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $identity = $admin->query('SELECT @@port AS port, @@datadir AS datadir')->fetch();
    cleanup5_assert((int) $identity['port'] === 33317 && realpath($identity['datadir']) === realpath($private . '/data'));
    $files = []; $databases = []; $preserved = [];
    $candidate = static function (string $path, string $owner) use (&$files, $private): void {
        cleanup5_assert(cleanup5_within($path, $owner) && cleanup5_within($owner, $private));
        $files[$path] = ['relative'=>str_replace('\\','/', substr($path, strlen($private) + 1)),
            'bytes'=>filesize($path), 'sha256'=>hash_file('sha256', $path)];
    };
    foreach (glob($private . '/restore5-*', GLOB_ONLYDIR) as $directory) {
        cleanup5_assert(cleanup5_within($directory, $private) && preg_match('/\Arestore5-([a-f0-9]{12})\z/', basename($directory), $match) === 1);
        $tag = $match[1];
        $result = json_decode(file_get_contents($directory . '/result.json'), true, 512, JSON_THROW_ON_ERROR);
        cleanup5_assert($result['failed'] === 0 && $result['passed'] === count($result['checks']));
        $preserved[$directory . '/result.json'] = hash_file('sha256', $directory . '/result.json');
        foreach (['sql'=>'s','full'=>'z'] as $type => $suffix) {
            $database = 'website_test_restore5_' . $tag . '_' . $type;
            $username = 'restore5_' . $tag . '_' . $suffix;
            cleanup5_assert($result['targets'][$type]['database'] === $database && $result['targets'][$type]['username'] === $username);
            $exists = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $exists->execute([$database]);
            $userExists = $admin->prepare("SELECT COUNT(*) FROM mysql.user WHERE User = ? AND Host = '127.0.0.1'");
            $userExists->execute([$username]);
            if ((int) $exists->fetchColumn() === 0) {
                cleanup5_assert((int) $userExists->fetchColumn() === 0); // An unexpected residual account requires manual review.
                continue;
            }
            cleanup5_assert((int) $userExists->fetchColumn() === 1);
            $path = $directory . '/connection-' . $type . '.json';
            cleanup5_assert(cleanup5_within($path, $directory));
            $targetConfig = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            cleanup5_assert($targetConfig['host'] === '127.0.0.1' && $targetConfig['port'] === 33317
                && $targetConfig['database'] === $database && $targetConfig['username'] === $username
                && realpath($targetConfig['datadir']) === realpath($private . '/data'));
            $target = new PDO('mysql:host=127.0.0.1;port=33317;dbname=' . $database . ';charset=utf8mb4', $username, $targetConfig['password'],
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
            $actual = $target->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@port AS port, @@datadir AS datadir')->fetch();
            cleanup5_assert($actual['db'] === $database && $actual['account'] === $username . '@127.0.0.1'
                && (int) $actual['port'] === 33317 && realpath($actual['datadir']) === realpath($private . '/data'));
            foreach ($target->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
                cleanup5_assert(str_starts_with($grant, 'GRANT USAGE ON *.* TO ') || str_starts_with($grant, 'GRANT SELECT ON `' . $database . '`.* TO '));
            }
            $names = $target->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); cleanup5_assert(count($names) === 17);
            foreach ($names as $name) {
                cleanup5_assert(preg_match('/\A[a-zA-Z0-9_]+\z/', $name) === 1);
                if ($name !== 'stage3_test_environment') { cleanup5_assert((int) $target->query('SELECT COUNT(*) FROM `' . $name . '`')->fetchColumn() === 0); }
            }
            cleanup5_assert(hash_equals($config['marker'], (string) $target->query('SELECT marker FROM stage3_test_environment WHERE id = 1')->fetchColumn()));
            $target = null;
            $databases[] = compact('database','username'); $candidate($path, $directory);
        }
    }
    foreach (glob($private . '/http-*', GLOB_ONLYDIR) as $directory) {
        $ledgerPath = $directory . '/backup-completeness-ledger.json';
        if (!is_file($ledgerPath)) { continue; }
        cleanup5_assert(cleanup5_within($directory, $private) && preg_match('/\Ahttp-crud3_[a-f0-9]{12}\z/', basename($directory)) === 1);
        $manifest = json_decode(file_get_contents($directory . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        cleanup5_assert(realpath($manifest['snapshot']) === realpath($directory) && $manifest['tag'] === substr(basename($directory), 5));
        $ledger = json_decode(file_get_contents($ledgerPath), true, 512, JSON_THROW_ON_ERROR);
        foreach (['sql'=>'.sql','full'=>'.zip'] as $type => $extension) {
            $path = $directory . '/backup-completeness-' . $type . $extension;
            if (is_file($path)) { cleanup5_assert(hash_file('sha256', $path) === $ledger[$type]['sha256']); $candidate($path, $directory); }
            $expected = $directory . '/backup-completeness-' . $type . '-expected.json';
            if (is_file($expected)) { $candidate($expected, $directory); }
        }
        foreach ([$ledgerPath,$directory . '/result.json',$directory . '/manifest.json'] as $path) { $preserved[$path] = hash_file('sha256', $path); }
    }
    // This is a failed HTTP response fixture, not a historical ZIP archive.
    $responseDirectory = $private . '/http-role3_108ed88ed2af';
    $response = $responseDirectory . '/backup-security-response.zip';
    if (is_file($response)) {
        $result = json_decode(file_get_contents($responseDirectory . '/result.json'), true, 512, JSON_THROW_ON_ERROR);
        cleanup5_assert($result['failed'] !== [] && filesize($response) === 54);
        $candidate($response, $responseDirectory);
    }
    $bytes = array_sum(array_column($files, 'bytes'));
    echo 'PLAN: restore databases=' . count($databases) . '; dummy/credential files=' . count($files) . '; bytes=' . $bytes . PHP_EOL;
    foreach ($databases as $target) { echo 'Owned isolated restore target: ' . $target['database'] . PHP_EOL; }
    if ($mode === '--apply') {
        foreach ($databases as $target) {
            // Only names derived from successful restore metadata on the verified testing instance reach DROP.
            $admin->exec('DROP DATABASE `' . $target['database'] . '`');
            $admin->exec('DROP USER ' . $admin->quote($target['username']) . "@'127.0.0.1'");
            $exists = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'); $exists->execute([$target['database']]);
            $userExists = $admin->prepare('SELECT COUNT(*) FROM mysql.user WHERE User = ?'); $userExists->execute([$target['username']]);
            cleanup5_assert((int) $exists->fetchColumn() === 0 && (int) $userExists->fetchColumn() === 0);
        }
        foreach ($files as $path => $entry) {
            cleanup5_assert(cleanup5_within($path, $private) && hash_file('sha256', $path) === $entry['sha256'] && unlink($path));
        }
        foreach ($preserved as $path => $hash) { cleanup5_assert(is_file($path) && hash_file('sha256', $path) === $hash); }
        cleanup5_assert(cleanup5_original_state() === $before);
        stage3_test_database();
        file_put_contents($private . '/cleanup-stage5-result.json', json_encode(['date'=>gmdate('c'), 'restore_targets_removed'=>$databases,
            'deleted_files'=>array_values($files), 'deleted_bytes'=>$bytes, 'source_and_historical_backup_hashes_unchanged'=>true,
            'testing_A_retained'=>true, 'evidence_hashes_unchanged'=>true], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo "PASS: owned restore databases/accounts and dummy artifacts removed; historical backups, source A and evidence retained.\n";
    } else { echo "DRY RUN: no database/file changes.\n"; }
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: cleanup stopped safely (' . get_class($exception) . '); inspect ownership before retry.' . PHP_EOL);
    exit(1);
}
