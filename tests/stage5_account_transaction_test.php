<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/stage3_database_preflight.php';
$pdo = stage3_test_database();
function db(): PDO { global $pdo; return $pdo; }
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/repositories.php';

if (in_array($argv[1] ?? '', ['--delete-worker','--account-worker'], true)) {
    try {
        $_SESSION = [];
        $operation = ($argv[1] ?? '') === '--delete-worker' ? 'delete' : ($argv[3] ?? '');
        if (!in_array($operation, ['delete','disable','demote'], true)) { exit(1); }
        if (($argv[1] ?? '') === '--account-worker') {
            $_SESSION['admin_username'] = $argv[4] ?? '';
        }
        echo "READY\n";
        flush();
        if (($argv[1] ?? '') === '--account-worker' && trim((string) fgets(STDIN)) !== 'GO') { exit(1); }
        $start = microtime(true);
        if ($operation === 'delete') {
            $result = delete_admin_user((string) ($argv[2] ?? ''));
        } else {
            $id = require_database_id($argv[2] ?? '');
            $statement = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
            $statement->execute([$id]); $row = $statement->fetch();
            if (!$row || !str_starts_with($row['username'], 'TEST_TAHAP5_TX_')) { exit(1); }
            $result = save_admin_user(['id'=>$id,'username'=>$row['username'],'full_name'=>$row['full_name'],
                'role'=>$operation === 'demote' ? 'editor' : 'superadmin',
                'is_active'=>$operation === 'disable' ? 0 : 1,'password'=>'']);
        }
        echo json_encode(['result'=>$result,'elapsed'=>microtime(true)-$start], JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) { exit(1); }
    exit;
}
if (($argv[1] ?? '') !== '--run') { exit("CLI only: --run required.\n"); }
$checks = 0;
$ids = [];
$child = null;
$workers = [];
function account_check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException('Failed assertion: ' . $label); }
    $checks++;
    echo '[OK] ' . $label . PHP_EOL;
}
function account_row(int $id): array|false
{
    $statement = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch();
}
try {
    account_check((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0, 'Testing accounts initially empty');
    $tag = 'TEST_TAHAP5_TX_' . bin2hex(random_bytes(6));
    foreach (['a','b'] as $suffix) {
        $pdo->prepare('INSERT INTO admins (username,full_name,role,is_active,password_hash) VALUES (?,?,?,?,?)')
            ->execute([$tag . '_' . $suffix,'Synthetic account','superadmin',1,password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT)]);
        $ids[] = (int) $pdo->lastInsertId();
    }
    $_SESSION = ['admin_username'=>$tag . '_actor'];
    $original = account_row($ids[0]);
    try {
        admin_account_transaction($pdo, static function () use ($pdo, $ids): array {
            $pdo->prepare('UPDATE admins SET full_name = ? WHERE id = ?')->execute(['Pending update',$ids[0]]);
            throw new RuntimeException('Synthetic middle-operation failure');
        });
        throw new LogicException('Failure swallowed');
    } catch (RuntimeException $exception) {
        account_check($exception->getMessage() === 'Synthetic middle-operation failure', 'Mid-operation error propagated');
    }
    account_check(account_row($ids[0]) === $original && !$pdo->inTransaction(), 'Mid-operation rollback restores all fields and releases transaction');
    $result = admin_account_transaction($pdo, static function () use ($pdo, $ids): array {
        $pdo->prepare('UPDATE admins SET full_name = ? WHERE id = ?')->execute(['Rejected update',$ids[0]]);
        return ['success'=>false];
    });
    account_check(!$result['success'] && account_row($ids[0]) === $original, 'Rejected result rolls back pending changes');
    $pdo->beginTransaction();
    try { admin_account_transaction($pdo, static fn()=>['success'=>true]); throw new LogicException('Nested transaction accepted'); }
    catch (RuntimeException $exception) { account_check($pdo->inTransaction(), 'Nested transaction rejected without taking ownership of caller transaction'); }
    $pdo->rollBack();
    try {
        save_admin_user(['id'=>$ids[0], 'username'=>$tag . '_b','full_name'=>'Changed','role'=>'editor','is_active'=>0,'password'=>'Synthetic-password-123']);
        throw new LogicException('Duplicate accepted');
    } catch (PDOException $exception) {
        account_check(account_row($ids[0]) === $original && !$pdo->inTransaction(), 'SQL account update failure preserves role/status/hash and releases locks');
    }
    $result = save_admin_user(['id'=>$ids[0],'username'=>$tag . '_a','full_name'=>'Updated','role'=>'superadmin','is_active'=>1,'password'=>'']);
    account_check($result['success'] && account_row($ids[0])['full_name'] === 'Updated', 'Normal account update commits');

    // The worker cannot evaluate the last-superadmin rule until the first transaction releases the account locks.
    $pdo->beginTransaction();
    $pdo->query('SELECT id FROM admins ORDER BY id FOR UPDATE')->fetchAll();
    $child = proc_open([PHP_BINARY,__FILE__,'--delete-worker',(string) $ids[1]], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
    account_check(is_resource($child), 'Concurrent testing worker started');
    fclose($pipes[0]);
    account_check(trim((string) fgets($pipes[1])) === 'READY', 'Concurrent worker verified database and reached operation');
    usleep(800000);
    account_check(proc_get_status($child)['running'], 'Concurrent worker waits for held account lock');
    $pdo->prepare('UPDATE admins SET is_active = 0 WHERE id = ?')->execute([$ids[0]]);
    $pdo->commit();
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($child); $child = null;
    $worker = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    account_check($exit === 0 && $errors === '' && !$worker['result']['success'], 'Worker rechecks policy after lock release and rejects last-superadmin deletion');
    account_check($worker['elapsed'] >= 0.7, 'Concurrent operation was delayed by account serialization');
    account_check((int) $pdo->query("SELECT COUNT(*) FROM admins WHERE role='superadmin' AND is_active=1")->fetchColumn() === 1
        && account_row($ids[1]) !== false, 'One active superadmin preserved after concurrent attempt');
    $result = save_admin_user(['id'=>$ids[1],'username'=>$tag . '_b','full_name'=>'Synthetic','role'=>'editor','is_active'=>1,'password'=>'']);
    account_check(!$result['success'] && account_row($ids[1])['role'] === 'superadmin', 'Last active superadmin demotion rejected');
    $_SESSION['admin_id'] = $ids[1];
    account_check(!delete_admin_user($ids[1])['success'], 'Current account cannot delete itself');
    $current = account_row($ids[1]);
    foreach ([['role'=>'superadmin','is_active'=>0], ['role'=>'editor','is_active'=>1]] as $change) {
        $result = save_admin_user(['id'=>$ids[1],'username'=>$current['username'],'full_name'=>$current['full_name'],
            'password'=>''] + $change);
        account_check(!$result['success'] && account_row($ids[1]) === $current, 'Current superadmin cannot disable/demote itself');
    }
    $_SESSION = ['admin_username'=>$tag . '_actor'];
    $result = save_admin_user(['id'=>$ids[1],'username'=>$current['username'],'full_name'=>$current['full_name'],
        'role'=>'superadmin','is_active'=>0,'password'=>'']);
    account_check(!$result['success'] && account_row($ids[1]) === $current, 'Last active superadmin deactivation rejected without mutation');

    foreach ([['delete','delete'],['disable','disable'],['demote','demote'],['delete','disable'],['delete','demote'],['disable','demote']] as $pair) {
        // Reset only exact owned synthetic records; no production data or schema is touched.
        foreach ($ids as $id) { $pdo->prepare('DELETE FROM admins WHERE id = ? AND username LIKE ?')->execute([$id,$tag . '%']); }
        $pairIds = [];
        foreach (['a','b'] as $suffix) {
            $pdo->prepare('INSERT INTO admins (username,full_name,role,is_active,password_hash) VALUES (?,?,?,?,?)')
                ->execute([$tag . '_' . $suffix,'Synthetic pair','superadmin',1,password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
            $pairIds[] = (int) $pdo->lastInsertId(); $ids[] = end($pairIds);
        }
        $label = implode('/', $pair);
        $pdo->beginTransaction();
        $pdo->query('SELECT id FROM admins ORDER BY id FOR UPDATE')->fetchAll();
        $workers = [];
        foreach ($pair as $index => $operation) {
            $process = proc_open([PHP_BINARY,__FILE__,'--account-worker',(string) $pairIds[$index],$operation,$tag . '_actor'],
                [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $workerPipes);
            account_check(is_resource($process), $label . ': repository worker started');
            $workers[] = ['process'=>$process,'pipes'=>$workerPipes];
            account_check(trim((string) fgets($workerPipes[1])) === 'READY', $label . ': worker ready before simultaneous release');
        }
        foreach ($workers as $worker) { fwrite($worker['pipes'][0], "GO\n"); fclose($worker['pipes'][0]); }
        usleep(400000);
        account_check(proc_get_status($workers[0]['process'])['running'] && proc_get_status($workers[1]['process'])['running'], $label . ': both operations wait behind verified account lock');
        $pdo->commit();
        $results = [];
        foreach ($workers as $index => $worker) {
            $output = stream_get_contents($worker['pipes'][1]); $errors = stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][1]); fclose($worker['pipes'][2]);
            $exit = proc_close($worker['process']); $workers[$index]['process'] = null;
            $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            account_check($exit === 0 && $errors === '', $label . ': concurrent worker completes normally');
            $results[] = $decoded['result']['success'];
        }
        $workers = [];
        account_check(count(array_filter($results)) === 1, $label . ': exactly one destructive account operation commits, other rejected');
        account_check((int) $pdo->query("SELECT COUNT(*) FROM admins WHERE role='superadmin' AND is_active=1")->fetchColumn() === 1
            && !$pdo->inTransaction(), $label . ': one active superadmin remains and no open transaction');
    }
    echo 'PASS: ' . $checks . ' account transaction/rollback/concurrency checks.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: Account transaction test (' . get_class($exception) . ').'
        . (str_starts_with($exception->getMessage(), 'Failed assertion: ') ? ' ' . $exception->getMessage() : '') . PHP_EOL);
    $failed = true;
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if (is_resource($child)) { proc_terminate($child); proc_close($child); }
    foreach ($workers as $worker) {
        if (is_resource($worker['process'])) { proc_terminate($worker['process']); proc_close($worker['process']); }
        foreach ($worker['pipes'] as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
    }
    foreach ($ids as $id) { $pdo->prepare('DELETE FROM admins WHERE id = ? AND username LIKE ?')->execute([$id,$tag . '%']); }
    if (isset($tag)) { $pdo->prepare('DELETE FROM admin_activity_logs WHERE username = ?')->execute([$tag . '_actor']); }
}
if ($failed ?? false) { exit(1); }
