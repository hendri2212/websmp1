<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (($argv[1] ?? '') !== '--run') { fwrite(STDERR, "Use --run <verified backup snapshot>.\n"); exit(1); }
require __DIR__ . '/stage3_database_preflight.php';

$checks = [];
$targets = [];
$restoredFiles = [];
$evidence = null;
$credentialFile = null;

function restore_check(bool $passed, string $label): void
{
    global $checks;
    $checks[] = compact('passed', 'label');
    echo ($passed ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$passed) { throw new RuntimeException('Restore verification failed: ' . $label); }
}

function restore_rows(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        ksort($row);
        $result[] = json_encode(array_map(static fn($value)=>$value === null ? null : (string) $value, $row), JSON_THROW_ON_ERROR);
    }
    sort($result, SORT_STRING);
    return $result;
}

function restore_database_state(PDO $pdo): string
{
    $state = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $name) {
        if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $name)) { throw new RuntimeException('Unexpected table identifier.'); }
        $state[$name] = [$pdo->query('SHOW CREATE TABLE `' . $name . '`')->fetch()['Create Table'],
            restore_rows($pdo->query('SELECT * FROM `' . $name . '`')->fetchAll())];
    }
    return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
}

try {
    $source = stage3_test_database();
    $private = realpath(dirname(__DIR__) . '/storage/testing/stage3-mariadb');
    $snapshot = realpath($argv[2] ?? '');
    restore_check(is_string($private) && is_string($snapshot) && !is_link($argv[2] ?? '')
        && dirname($snapshot) === $private && preg_match('/\Ahttp-crud3_[a-f0-9]{12}\z/', basename($snapshot)) === 1,
        'Backup snapshot belongs to verified private testing root');
    $manifest = json_decode(file_get_contents($snapshot . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $result = json_decode(file_get_contents($snapshot . '/result.json'), true, 512, JSON_THROW_ON_ERROR);
    $ledger = json_decode(file_get_contents($snapshot . '/backup-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
    restore_check(realpath($manifest['snapshot']) === $snapshot && $result['failed'] === []
        && $result['passed'] === $result['checks'], 'Application backup completeness run passed before restore');
    $sourceBefore = restore_database_state($source);
    foreach ($source->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $name) {
        if ($name !== 'stage3_test_environment') {
            restore_check((int) $source->query('SELECT COUNT(*) FROM `' . $name . '`')->fetchColumn() === 0,
                'Source A cleaned after backup: ' . $name);
        }
    }
    $tag = bin2hex(random_bytes(6));
    $evidence = $private . '/restore5-' . $tag;
    restore_check(mkdir($evidence, 0700), 'New private restore evidence directory created');
    $adminConfig = json_decode(file_get_contents($private . '/bootstrap-admin.json'), true, 512, JSON_THROW_ON_ERROR);
    $admin = new PDO('mysql:host=127.0.0.1;port=33317;charset=utf8mb4', $adminConfig['username'], $adminConfig['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $identity = $admin->query('SELECT @@port AS port, @@datadir AS datadir')->fetch();
    restore_check((int) $identity['port'] === 33317 && realpath($identity['datadir']) === realpath($private . '/data'),
        'Provisioning connection is actual isolated instance, not default/production server');
    $sourceEncoding = $source->query("SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation_name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()")->fetch();
    restore_check($sourceEncoding['charset'] === 'utf8mb4' && preg_match('/\Autf8mb4_[a-z0-9_]+\z/', $sourceEncoding['collation_name']) === 1,
        'Source encoding/collation safe for explicit target creation');

    foreach (['sql', 'full'] as $type) {
        $artifact = $snapshot . '/backup-completeness-' . $type . ($type === 'sql' ? '.sql' : '.zip');
        restore_check(!is_link($artifact) && hash_file('sha256', $artifact) === $ledger[$type]['sha256'], $type . ': application backup hash matches completeness ledger');
        $expected = json_decode(file_get_contents($snapshot . '/backup-completeness-' . $type . '-expected.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (array_keys($expected['tables']) as $table) {
            restore_check(preg_match('/\A[a-zA-Z0-9_]+\z/', $table) === 1, $type . ': safe table identifier ' . $table);
        }
        $database = 'website_test_restore5_' . $tag . '_' . $type;
        $username = 'restore5_' . $tag . '_' . ($type === 'sql' ? 's' : 'z');
        $password = bin2hex(random_bytes(32));
        $exists = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $exists->execute([$database]);
        $userExists = $admin->prepare('SELECT COUNT(*) FROM mysql.user WHERE User = ?');
        $userExists->execute([$username]);
        restore_check((int) $exists->fetchColumn() === 0 && (int) $userExists->fetchColumn() === 0, $type . ': database B and restricted account are new');
        $admin->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE ' . $sourceEncoding['collation_name']);
        $account = $admin->quote($username) . "@'127.0.0.1'";
        $admin->exec('CREATE USER ' . $account . ' IDENTIFIED BY ' . $admin->quote($password));
        $admin->exec('GRANT SELECT, INSERT, DELETE, CREATE, DROP, INDEX ON `' . $database . '`.* TO ' . $account);
        $target = new PDO('mysql:host=127.0.0.1;port=33317;dbname=' . $database . ';charset=utf8mb4', $username, $password,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false, PDO::MYSQL_ATTR_MULTI_STATEMENTS=>false]);
        $identity = $target->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@port AS port, @@datadir AS datadir')->fetch();
        restore_check($identity['db'] === $database && $identity['account'] === $username . '@127.0.0.1'
            && (int) $identity['port'] === 33317 && realpath($identity['datadir']) === realpath($private . '/data'), $type . ': B identity verified before import');
        foreach ($target->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
            restore_check(str_starts_with($grant, 'GRANT USAGE ON *.* TO ') || str_contains($grant, ' ON `' . $database . '`.* TO '), $type . ': importer grants confined to B');
        }
        $denied = false;
        try { $target->query('SELECT marker FROM website_sekolah_test_stage3.stage3_test_environment'); }
        catch (PDOException $exception) { $denied = (int) ($exception->errorInfo[1] ?? 0) === 1142; }
        restore_check($denied, $type . ': B account cannot read A through qualified SQL');
        restore_check($target->query('SHOW TABLES')->fetchAll() === [], $type . ': B contains zero tables before restore');
        $targets[$type] = ['database'=>$database, 'username'=>$username, 'password'=>$password, 'pdo'=>$target,
            'expected'=>$expected, 'account'=>$account];
        $connection = ['host'=>'127.0.0.1', 'port'=>33317, 'database'=>$database, 'username'=>$username,
            'password'=>$password, 'datadir'=>str_replace('\\','/', realpath($private . '/data')), 'source_snapshot'=>basename($snapshot)];
        $connectionPath = $evidence . '/connection-' . $type . '.json';
        file_put_contents($connectionPath, json_encode($connection, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)); chmod($connectionPath, 0600);
        if ($type === 'full') {
            $zip = new ZipArchive();
            restore_check($zip->open($artifact) === true, 'full: ZIP opens for recovery');
            $sql = $zip->getFromName('database/backup-website_sekolah_test_stage3.sql');
        } else { $sql = file_get_contents($artifact); }
        restore_check(is_string($sql) && str_starts_with($sql, '-- Backup database website sekolah'), $type . ': importing data backup, not installer/seed');
        $credentialFile = $evidence . '/import-client.cnf';
        file_put_contents($credentialFile, "[client]\nhost=127.0.0.1\nport=33317\nprotocol=tcp\nuser=$username\npassword=$password\n");
        chmod($credentialFile, 0600);
        // Use the actual MariaDB SQL importer; credentials never appear in command arguments.
        $process = proc_open(['C:/xampp/mysql/bin/mysql.exe', '--defaults-extra-file=' . $credentialFile,
            '--database=' . $database, '--default-character-set=utf8mb4', '--binary-mode', '--skip-reconnect', '--batch'],
            [0=>['pipe','r'], 1=>['file',$evidence . '/import-' . $type . '.out.log','a'], 2=>['file',$evidence . '/import-' . $type . '.err.log','a']], $pipes, null, null, ['bypass_shell'=>true]);
        restore_check(is_resource($process), $type . ': MariaDB importer process started');
        $written = 0;
        while ($written < strlen($sql)) {
            $bytes = fwrite($pipes[0], substr($sql, $written));
            if ($bytes === false || $bytes === 0) { break; }
            $written += $bytes;
        }
        fclose($pipes[0]);
        $exit = proc_close($process);
        unlink($credentialFile); $credentialFile = null;
        restore_check($written === strlen($sql) && $exit === 0, $type . ': backup SQL actually imported successfully into empty B');
        $names = $target->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $expectedNames = array_keys($expected['tables']); sort($names); sort($expectedNames);
        restore_check($names === $expectedNames, $type . ': restored table set/count matches A snapshot');
        $total = 0;
        foreach ($expected['tables'] as $name => $table) {
            $schema = $target->query('SHOW CREATE TABLE `' . $name . '`')->fetch()['Create Table'];
            restore_check($schema === $table['schema'], $type . ': full structure/index/default/engine/encoding matches: ' . $name);
            $rows = $target->query('SELECT * FROM `' . $name . '`')->fetchAll();
            $total += count($rows);
            restore_check(restore_rows($rows) === restore_rows($table['rows']), $type . ': record count and all column values match: ' . $name);
        }
        $encoding = $target->query("SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation_name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()")->fetch();
        restore_check($encoding === $sourceEncoding, $type . ': database encoding/collation matches A');
        restore_check((int) $target->query('SELECT @@SESSION.foreign_key_checks')->fetchColumn() === 1, $type . ': new verification connection enforces foreign keys');
        $orphans = $target->query('SELECT COUNT(*) FROM admin_activity_logs l LEFT JOIN admins a ON a.id = l.admin_id WHERE l.admin_id IS NOT NULL AND (a.id IS NULL OR a.username <> l.username)')->fetchColumn();
        restore_check((int) $orphans === 0, $type . ': activity log/account logical relation has no orphan or identity mismatch');
        $accounts = $target->query('SELECT * FROM admins')->fetchAll();
        restore_check(count($accounts) === 3 && count(array_filter($accounts, static fn($row)=>password_get_info($row['password_hash'])['algoName'] !== 'unknown')) === 3,
            $type . ': all three synthetic account hashes/roles/statuses preserved (exact rows compared)');
        $profile = $target->query("SELECT setting_value FROM site_settings WHERE setting_key = 'schoolProfile'")->fetchColumn();
        restore_check(is_string($profile) && str_starts_with(json_decode($profile, true, 512, JSON_THROW_ON_ERROR)['name'], 'TEST_TAHAP3_BACKUP_PROFIL_'), $type . ': restored profile JSON valid and synthetic');
        $content = $target->query('SELECT content FROM news LIMIT 1')->fetchColumn();
        restore_check(is_string($content) && str_contains($content, json_decode('"\u00e9\u4e2d"', true)) && str_contains($content, "\n"), $type . ': Unicode/newline payload survives actual SQL import');
        if ($type === 'full') {
            $fileRoot = $evidence . '/recovered-files'; mkdir($fileRoot, 0700);
            foreach ($expected['files'] as $name => $hash) {
                restore_check(preg_match('~\Afiles/(public|storage)/uploads/[a-zA-Z0-9_./-]+\z~', $name) === 1
                    && !in_array('..', explode('/', $name), true) && !in_array('.', explode('/', $name), true), 'full: safe recovery path ' . $name);
                $path = $fileRoot . '/' . substr($name, 6);
                if (!is_dir(dirname($path))) { mkdir(dirname($path), 0700, true); }
                $bytes = $zip->getFromName($name);
                restore_check(is_string($bytes) && file_put_contents($path, $bytes) === strlen($bytes) && hash_file('sha256', $path) === $hash, 'full: file actually restored and hash matches: ' . $name);
                $restoredFiles[$path] = $hash;
            }
            $zip->close();
            foreach (['news'=>'image_path','gallery_items'=>'image_path','staff_members'=>'photo_path','school_documents'=>'file_path'] as $table => $column) {
                foreach ($target->query('SELECT `' . $column . '` AS path FROM `' . $table . '`')->fetchAll() as $row) {
                    restore_check(is_file($fileRoot . '/' . ltrim($row['path'], '/')), 'full: restored record points to recovered file: ' . $table);
                }
            }
        }
        $targets[$type]['restored_rows'] = $total;
        echo 'Verified target B: ' . $database . '; tables=' . count($names) . '; rows=' . $total . PHP_EOL;
    }
    restore_check(restore_database_state($source) === $sourceBefore, 'Restore did not change any source A table structure/record');

    // Remove only restored records whose complete values still match the owned synthetic snapshot.
    foreach ($targets as $type => $entry) {
        $target = $entry['pdo'];
        $target->beginTransaction();
        try {
            foreach ($entry['expected']['tables'] as $name => $table) {
                restore_check(restore_rows($target->query('SELECT * FROM `' . $name . '`')->fetchAll()) === restore_rows($table['rows']), $type . ': cleanup ownership intact: ' . $name);
                if ($name === 'stage3_test_environment') { continue; }
                $delete = $target->prepare('DELETE FROM `' . $name . '` WHERE id = ?');
                foreach ($table['rows'] as $row) { $delete->execute([$row['id']]); }
                restore_check((int) $target->query('SELECT COUNT(*) FROM `' . $name . '`')->fetchColumn() === 0, $type . ': synthetic rows cleaned: ' . $name);
            }
            $target->commit();
        } catch (Throwable $exception) { $target->rollBack(); throw $exception; }
        $admin->exec('REVOKE INSERT, DELETE, CREATE, DROP, INDEX ON `' . $entry['database'] . '`.* FROM ' . $entry['account']);
        restore_check(count($target->query('SHOW TABLES')->fetchAll()) === 17, $type . ': restored structures retained; no cleanup DROP TABLE/COLUMN');
        $readonly = true;
        foreach ($target->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
            $readonly = $readonly && (str_starts_with($grant, 'GRANT USAGE ON *.* TO ') || str_starts_with($grant, 'GRANT SELECT ON `' . $entry['database'] . '`.* TO '));
        }
        restore_check($readonly, $type . ': testing account reduced to read-only after cleanup');
    }
    foreach ($restoredFiles as $path => $hash) {
        restore_check(!is_link($path) && hash_file('sha256', $path) === $hash && unlink($path), 'Recovered synthetic file cleaned after verification');
    }
    restore_check(restore_database_state($source) === $sourceBefore, 'Source A remains unchanged after target cleanup');
} catch (Throwable $exception) {
    $checks[] = ['passed'=>false, 'label'=>'Restore runner stopped safely (' . get_class($exception) . '); inspect private evidence before retry.'];
    fwrite(STDERR, end($checks)['label'] . PHP_EOL);
} finally {
    if (is_string($credentialFile) && is_file($credentialFile)) { unlink($credentialFile); }
    $summary = ['date'=>gmdate('c'), 'checks'=>$checks, 'passed'=>count(array_filter($checks, static fn($check)=>$check['passed'])),
        'failed'=>count(array_filter($checks, static fn($check)=>!$check['passed'])),
        'targets'=>array_map(static fn($entry)=>['database'=>$entry['database'], 'username'=>$entry['username'], 'restored_rows'=>$entry['restored_rows'] ?? null], $targets)];
    if (is_string($evidence) && is_dir($evidence)) { file_put_contents($evidence . '/result.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)); }
    echo 'RESULT: ' . $summary['passed'] . '/' . count($checks) . '; failures=' . $summary['failed'] . PHP_EOL;
    if ($evidence !== null) { echo 'Private restore evidence: ' . $evidence . PHP_EOL; }
}
exit($summary['failed'] ? 1 : 0);
