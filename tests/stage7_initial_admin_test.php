<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (($argv[1] ?? '') !== '--run') { exit("Use --run with the verified isolated testing database.\n"); }
require __DIR__ . '/stage3_database_preflight.php';
$pdo = stage3_test_database(); $directory = null; $children = []; $ownedNames = []; $checks = 0; $failed = false;
$assert = static function (bool $condition) use (&$checks): void {
    if (!$condition) { throw new RuntimeException('Initial administrator assertion failed'); } $checks++;
};
$start = static function (string $username, string $password, array $arguments = []) use (&$directory, &$children): int {
    $process = proc_open(array_merge([PHP_BINARY,$directory . '/database/initialize_admin.php'],
        $arguments ?: ['--initialize',$username,'Synthetic initial administrator']),
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$directory);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start isolated initializer'); }
    fwrite($pipes[0],$password . "\n"); fclose($pipes[0]);
    $children[] = ['process'=>$process,'pipes'=>$pipes]; return array_key_last($children);
};
$finish = static function (int $index, string $password) use (&$children,$assert): int {
    $entry=$children[$index]; $output=stream_get_contents($entry['pipes'][1]); $error=stream_get_contents($entry['pipes'][2]);
    fclose($entry['pipes'][1]); fclose($entry['pipes'][2]); $code=proc_close($entry['process']); $children[$index]['process']=null;
    $assert(!str_contains($output . $error,$password) && !preg_match('/\$2[aby]\$|SQLSTATE|Stack trace|mysql:host=/', $output . $error));
    $assert($code === 0 ? $output === "Initial administrator created. No existing accounts changed.\n" && $error === ''
        : $code === 1 && $output === '' && str_contains($error,'Initialization refused or failed.'));
    return $code;
};
try {
    $assert((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0);
    $beforeSchema = preg_replace('/ AUTO_INCREMENT=\d+/', '', $pdo->query('SHOW CREATE TABLE admins')->fetch(PDO::FETCH_NUM)[1]);
    $schema=file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $assert(!preg_match('/INSERT\s+INTO\s+`?admins\b|\$2[aby]\$/i',$schema));
    $private=realpath(dirname(__DIR__) . '/storage/testing'); $assert(is_string($private) && !is_link($private));
    $directory=$private . '/stage7-initial-' . bin2hex(random_bytes(8));
    mkdir($directory,0700); mkdir($directory . '/database'); mkdir($directory . '/includes');
    copy(dirname(__DIR__) . '/database/initialize_admin.php',$directory . '/database/initialize_admin.php');
    $assert(hash_file('sha256',$directory . '/database/initialize_admin.php') === hash_file('sha256',dirname(__DIR__) . '/database/initialize_admin.php'));
    file_put_contents($directory . '/includes/database.php','<?php require_once ' . var_export(__DIR__ . '/stage3_database_preflight.php',true)
        . '; function db(): PDO { return stage3_test_database(); }');
    $tag='TEST_TAHAP7_INIT_' . bin2hex(random_bytes(6)); $password='TEST_' . bin2hex(random_bytes(24));
    $ownedNames=[$tag . '_a',$tag . '_b',$tag . '_c'];
    foreach (['', 'short', str_repeat('x',73), "safe\0invalidpassword"] as $invalid) {
        $index=$start($ownedNames[0],$invalid);
        $entry=$children[$index]; $out=stream_get_contents($entry['pipes'][1]); $err=stream_get_contents($entry['pipes'][2]);
        fclose($entry['pipes'][1]); fclose($entry['pipes'][2]); $code=proc_close($entry['process']); $children[$index]['process']=null;
        $assert($code === 1 && $out === '' && str_contains($err,'Initialization refused or failed.'));
        $assert((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0);
    }
    $assert($finish($start($ownedNames[0],$password),$password) === 0);
    $row=$pdo->query('SELECT * FROM admins')->fetch();
    $assert($row['username'] === $ownedNames[0] && $row['role'] === 'superadmin' && (int) $row['is_active'] === 1);
    $assert(password_verify($password,$row['password_hash']) && $row['password_hash'] !== $password);
    $pdo->prepare("UPDATE admins SET role='editor',is_active=0 WHERE id=? AND username=?")->execute([$row['id'],$ownedNames[0]]);
    $before=$pdo->query('SELECT * FROM admins')->fetchAll();
    $assert($finish($start($ownedNames[1],$password),$password) === 1);
    $assert($before === $pdo->query('SELECT * FROM admins')->fetchAll());
    $pdo->prepare('DELETE FROM admins WHERE id=? AND username=?')->execute([$row['id'],$ownedNames[0]]);
    $assert((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0);
    $one=$start($ownedNames[1],$password); $two=$start($ownedNames[2],$password);
    $results=[$finish($one,$password),$finish($two,$password)]; sort($results);
    $assert($results === [0,1]);
    $rows=$pdo->query('SELECT * FROM admins')->fetchAll();
    $assert(count($rows) === 1 && in_array($rows[0]['username'],$ownedNames,true) && password_verify($password,$rows[0]['password_hash']));
    $lock='initial-admin-' . substr(hash('sha256',(string) $pdo->query('SELECT DATABASE()')->fetchColumn()),0,48);
    $statement=$pdo->prepare('SELECT IS_FREE_LOCK(?)'); $statement->execute([$lock]); $assert((int) $statement->fetchColumn() === 1);
    $assert($beforeSchema === preg_replace('/ AUTO_INCREMENT=\d+/', '', $pdo->query('SHOW CREATE TABLE admins')->fetch(PDO::FETCH_NUM)[1]));
    echo 'PASS: ' . $checks . ' isolated initial-account checks; no production database, passwords or hashes printed.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR,'FAIL: isolated initial-account test (' . get_class($exception) . ').' . PHP_EOL); $failed=true;
} finally {
    foreach ($children as $entry) { if (is_resource($entry['process'])) { proc_terminate($entry['process']); proc_close($entry['process']); } }
    foreach ($ownedNames as $name) { $pdo->prepare('DELETE FROM admins WHERE username=?')->execute([$name]); }
    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() !== 0) { $failed=true; }
    if (is_string($directory) && is_dir($directory) && !is_link($directory)
        && str_starts_with(realpath($directory),realpath(dirname(__DIR__) . '/storage/testing') . DIRECTORY_SEPARATOR)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
exit($failed ? 1 : 0);
