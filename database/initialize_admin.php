<?php

declare(strict_types=1);
require_once __DIR__ . '/../app/core/paths.php';

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (count($argv) !== 4 || ($argv[1] ?? '') !== '--initialize') {
    fwrite(STDERR, "Use --initialize <username> <full-name> with a private password pipe on STDIN.\n");
    exit(1);
}

$pdo = null; $locked = false; $lockName = null; $password = null; $failed = false;
try {
    if (stream_isatty(STDIN)) { throw new RuntimeException('Private input required'); }
    $username = $argv[2]; $fullName = $argv[3];
    $password = rtrim((string) stream_get_contents(STDIN, 1025), "\r\n");
    if (!preg_match('/\A[a-zA-Z0-9_.-]{3,50}\z/', $username)
        || !mb_check_encoding($fullName, 'UTF-8') || trim($fullName) === '' || mb_strlen($fullName, 'UTF-8') > 120
        || preg_match('/[\x00-\x1f\x7f]/', $fullName)
        || strlen($password) < 12 || strlen($password) > 72 || trim($password) === ''
        || preg_match('/[\x00-\x1f\x7f]/', $password)) {
        throw new RuntimeException('Invalid explicit initial account');
    }
    require APP_ROOT . '/app/core/database.php';
    $pdo = db();
    if (!$pdo) { throw new RuntimeException('Database unavailable'); }
    $database = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!is_string($database) || $database === '') { throw new RuntimeException('Database not selected'); }
    $lockName = 'initial-admin-' . substr(hash('sha256', $database), 0, 48);
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)'); $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) { throw new RuntimeException('Initialization lock unavailable'); }
    $locked = true;
    $engine = $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins'")->fetchColumn();
    if ($engine !== 'InnoDB') { throw new RuntimeException('Transactional account storage required'); }
    $pdo->beginTransaction();
    if ($pdo->query('SELECT id FROM admins LIMIT 1 FOR UPDATE')->fetchColumn() !== false) {
        throw new RuntimeException('Existing accounts must not be modified');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $password = null;
    $statement = $pdo->prepare("INSERT INTO admins (username, full_name, role, is_active, password_hash) VALUES (?, ?, 'superadmin', 1, ?)");
    $statement->execute([$username, $fullName, $hash]);
    if ($statement->rowCount() !== 1) { throw new RuntimeException('Account not inserted'); }
    $pdo->commit();
    echo "Initial administrator created. No existing accounts changed.\n";
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        try { $pdo->rollBack(); } catch (Throwable $exception) { /* Close the connection without exposing its error. */ }
    }
    fwrite(STDERR, "Initialization refused or failed. Verify private configuration, input and empty account storage.\n");
    $failed = true;
} finally {
    $password = null;
    if ($locked && $pdo instanceof PDO && is_string($lockName)) {
        try { $release = $pdo->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lockName]); }
        catch (Throwable $exception) { /* Connection closure also releases the advisory lock. */ }
    }
}
exit($failed ? 1 : 0);
