<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

// A PDO test double keeps these checks away from the school's database.
final class AuthTestDatabase extends PDO
{
    public array $accounts = [];
    public array $executed = [];
    public bool $unavailable = false;
    public bool $queryFails = false;
    public bool $connectionThrows = false;
    public ?string $failureStage = null;

    public function __construct() {}

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if ($this->queryFails) {
            throw new PDOException('Simulated database failure');
        }
        return new AuthTestStatement($this, $query);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $statement = new AuthTestStatement($this, $query);
        $statement->execute();
        return $statement;
    }

    public function exec(string $statement): int|false
    {
        $this->executed[] = $statement;
        return 0;
    }
}

final class AuthTestStatement extends PDOStatement
{
    private array $rows = [];

    public function __construct(private AuthTestDatabase $database, private string $sql) {}

    public function execute(?array $params = null): bool
    {
        if ($this->database->failureStage === 'execute') {
            throw new PDOException('Simulated private execute failure');
        }
        $this->database->executed[] = $this->sql;
        $this->rows = [];
        if ($this->sql === 'SHOW COLUMNS FROM admins') {
            $this->rows = array_map(static fn(string $name): array => ['Field' => $name], ['id', 'username', 'full_name', 'role', 'is_active', 'password_hash']);
        } elseif ($this->sql === 'SHOW COLUMNS FROM admin_activity_logs') {
            $this->rows = array_map(static fn(string $name): array => ['Field' => $name], ['id', 'admin_id', 'username', 'role', 'action', 'module', 'entity_type', 'entity_id', 'description', 'metadata', 'ip_address', 'user_agent', 'created_at']);
        } elseif (str_starts_with($this->sql, 'SELECT * FROM admins WHERE username =')) {
            foreach ($this->database->accounts as $account) {
                if ($account['username'] === ($params['username'] ?? '') && $account['is_active'] === 1) {
                    $this->rows[] = $account;
                }
            }
        } elseif (str_starts_with($this->sql, 'SELECT username, role, is_active FROM admins WHERE id =')) {
            $account = $this->database->accounts[$params['id'] ?? 0] ?? null;
            $this->rows = $account ? [$account] : [];
        } elseif (!str_starts_with($this->sql, 'INSERT INTO admin_activity_logs')) {
            throw new RuntimeException('Unexpected SQL in auth test: ' . $this->sql);
        }
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->database->failureStage === 'fetch') {
            throw new PDOException('Simulated private fetch failure');
        }
        return array_shift($this->rows) ?? false;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->rows;
    }
}

final class AuthTestRedirect extends RuntimeException {}

$testDatabase = new AuthTestDatabase();
function db(): ?PDO
{
    global $testDatabase;
    if ($testDatabase->connectionThrows) {
        throw new PDOException('Simulated private connection failure');
    }
    return $testDatabase->unavailable ? null : $testDatabase;
}

function redirect_to(string $path): void
{
    throw new AuthTestRedirect($path);
}

require __DIR__ . '/../includes/auth.php';

$sessionPath = __DIR__ . '/.tmp_sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
session_save_path($sessionPath);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_cookies', '0');
ob_start();
session_start();

$checks = 0;
function auth_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
    echo '[OK] ' . $label . PHP_EOL;
}

function expect_login_redirect(): void
{
    try {
        require_admin();
    } catch (AuthTestRedirect $redirect) {
        auth_assert($redirect->getMessage() === 'admin/login.php', 'Unauthenticated guard redirects to login');
        return;
    }
    throw new RuntimeException('Unauthenticated guard granted access');
}

$password = 'AuthTest-password-123';
$testDatabase->accounts = [
    1 => ['id' => 1, 'username' => 'admin', 'role' => 'superadmin', 'is_active' => 0, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)],
    2 => ['id' => 2, 'username' => 'editor-test', 'role' => 'editor', 'is_active' => 1, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)],
    3 => ['id' => 3, 'username' => 'ppdb-test', 'role' => 'ppdb', 'is_active' => 1, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)],
];

$exitCode = 0;
if (($argv[1] ?? '') === '--guard-probe') {
    $probeRole = $argv[2] ?? '';
    $probePermission = $argv[3] ?? '';
    $testDatabase->accounts[2]['role'] = $probeRole;
    $_SESSION = $probeRole === 'anonymous' ? [] : [
        'admin_logged_in' => true, 'admin_id' => 2,
        'admin_username' => 'editor-test', 'admin_role' => 'superadmin',
    ];

    // Render the real denial template with harmless presentation fixtures.
    function site_content(): array { return ['schoolProfile' => ['logoPath' => '/public/logo-sekolah.png']]; }
    function db_available(): bool { return false; }
    function e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    function base_url(string $path = ''): string { return '/' . ltrim($path, '/'); }
    function asset(string $path): string { return base_url($path); }
    function theme_css_variables(array $theme = []): string { return ''; }
    function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="test-token">'; }

    $probeResult = ['status' => 200, 'allowed' => false, 'redirect' => null];
    register_shutdown_function(static function () use (&$probeResult): void {
        $html = ob_get_clean();
        $probeResult['status'] = http_response_code() ?: $probeResult['status'];
        $probeResult['denial_rendered'] = str_contains($html, 'Akses Ditolak');
        preg_match('~<nav class="admin-nav">(.*?)</nav>~s', $html, $navigation);
        preg_match_all('~<a href="/admin/([^"?]+)"~', $navigation[1] ?? '', $links);
        $probeResult['menus'] = $links[1];
        $_SESSION = [];
        session_destroy();
        echo json_encode($probeResult) . PHP_EOL;
    });
    try {
        require_admin_permission($probePermission);
        $probeResult['allowed'] = true;
        require __DIR__ . '/../templates/admin_header.php';
        require __DIR__ . '/../templates/admin_footer.php';
    } catch (AuthTestRedirect $redirect) {
        $probeResult['status'] = 302;
        $probeResult['redirect'] = $redirect->getMessage();
    }
    exit;
}

try {
    auth_assert(!admin_is_logged_in() && !admin_can('dashboard'), 'Anonymous session denied');
    expect_login_redirect();
    $_SESSION = ['admin_logged_in' => true, 'admin_username' => 'admin', 'admin_role' => 'superadmin'];
    auth_assert(!admin_is_logged_in() && !admin_can('admins'), 'Incomplete session cannot bypass authentication');
    $_SESSION = ['admin_username' => 'admin'];
    auth_assert(admin_role() === '', 'Username admin has no implicit superadmin role');

    ensure_admin_role_columns();
    auth_assert(!array_filter($testDatabase->executed, static fn(string $sql): bool => str_starts_with($sql, 'UPDATE admins')), 'Role helper never reactivates or promotes admin');
    auth_assert(!admin_login('admin', $password), 'Inactive admin cannot log in');
    $testDatabase->accounts[1]['is_active'] = 1;
    auth_assert(admin_login('admin', $password) && admin_can('admins'), 'Active database superadmin can log in');
    $testDatabase->accounts[1]['is_active'] = 0;
    auth_assert(!admin_can('admins') && !admin_login('admin', $password), 'Deactivated admin stays inactive and loses access');
    auth_assert(!admin_login('missing', $password), 'Unknown account denied');
    auth_assert(!admin_login('editor-test', 'wrong'), 'Wrong password denied');
    $originalHash = $testDatabase->accounts[2]['password_hash'];
    $testDatabase->accounts[2]['password_hash'] = $password;
    auth_assert(!admin_login('editor-test', $password), 'Plaintext stored password is not accepted');
    $testDatabase->accounts[2]['password_hash'] = $originalHash;
    auth_assert(!admin_login(['editor-test'], $password) && !admin_login('editor-test', [$password]), 'Array credentials rejected');
    auth_assert(!admin_login("editor-test' OR 1=1 --", $password), 'SQL-like username treated as a literal credential');

    $_GET = ['role' => 'superadmin', 'admin_id' => 1];
    $_POST = ['role' => 'superadmin', 'admin_role' => 'superadmin'];
    $_REQUEST = array_merge($_GET, $_POST);
    $anonymousId = session_id();
    auth_assert(admin_login('editor-test', $password), 'Hash-verified active account logs in');
    auth_assert(session_id() !== $anonymousId && !is_file($sessionPath . '/sess_' . $anonymousId), 'Login rotates and deletes old session ID');
    auth_assert($_SESSION['admin_id'] === 2 && admin_role() === 'editor', 'Session identity and role come from database, not request');
    auth_assert(admin_can('news') && !admin_can('admins') && !admin_can('ppdb_applications'), 'Editor authorization boundaries');

    $_SESSION['admin_role'] = 'superadmin';
    auth_assert(!admin_can('admins') && admin_role() === 'editor', 'Stale or altered session role replaced by database role');
    $testDatabase->accounts[2]['role'] = 'superadmin';
    auth_assert(admin_can('admins'), 'Database role update takes effect');
    $testDatabase->accounts[2]['role'] = 'editor';
    auth_assert(!admin_can('admins'), 'Role demotion removes access, including within the same request');
    $testDatabase->accounts[2]['is_active'] = 0;
    auth_assert(!admin_can('news') && empty($_SESSION['admin_logged_in']), 'Deactivation revokes existing authenticated session');
    expect_login_redirect();
    auth_assert(!admin_login('editor-test', $password), 'Deactivated account cannot log in again');
    $testDatabase->accounts[2]['is_active'] = 1;

    auth_assert(admin_login('ppdb-test', $password), 'PPDB account login');
    auth_assert(admin_can('ppdb_content') && !admin_can('ppdb_applications') && !admin_can('documents') && !admin_can('admins'), 'PPDB information-only authorization boundaries');
    unset($testDatabase->accounts[3]);
    auth_assert(!admin_is_logged_in(), 'Deleted account loses existing session');

    $testDatabase->accounts[2]['role'] = 'unknown';
    auth_assert(!admin_login('editor-test', $password), 'Unknown database role cannot log in');
    $testDatabase->accounts[2]['role'] = 'editor';
    auth_assert(admin_login('editor-test', $password), 'Login before unknown-role session check');
    $testDatabase->accounts[2]['role'] = 'unknown';
    auth_assert(!admin_can('dashboard'), 'Unknown role revokes existing session');
    $testDatabase->accounts[2]['role'] = 'editor';

    auth_assert(admin_login('editor-test', $password), 'Login before database outage');
    $testDatabase->unavailable = true;
    auth_assert(!admin_is_logged_in() && !admin_can('news'), 'Database outage denies and clears authenticated access');
    auth_assert(!admin_login('editor-test', $password), 'Database outage prevents login');
    $testDatabase->unavailable = false;
    auth_assert(admin_login('editor-test', $password), 'Login before query failure');
    $testDatabase->queryFails = true;
    auth_assert(!admin_can('news'), 'Database query failure denies access');
    auth_assert(!admin_login('editor-test', $password), 'Database query failure prevents login');
    $testDatabase->queryFails = false;

    foreach (['connection', 'execute', 'fetch'] as $stage) {
        auth_assert(admin_login('editor-test', $password), 'Login before failure at ' . $stage);
        $testDatabase->connectionThrows = $stage === 'connection';
        $testDatabase->failureStage = $stage === 'connection' ? null : $stage;
        ob_start();
        $accessGranted = admin_can('news');
        $loginGranted = admin_login('editor-test', $password);
        $publicOutput = ob_get_clean();
        auth_assert(!$accessGranted && !$loginGranted && $publicOutput === '' && empty($_SESSION['admin_logged_in']), 'Failure at ' . $stage . ' denies access without exposing database error');
        expect_login_redirect();
        $testDatabase->connectionThrows = false;
        $testDatabase->failureStage = null;
    }
    $testDatabase->accounts[1]['is_active'] = 1;
    auth_assert(admin_login('admin', $password) && admin_can('admins'), 'Active superadmin access works after database recovery');
    $testDatabase->accounts[1]['is_active'] = 0;

    auth_assert(admin_login('editor-test', $password), 'Login before logout');
    $_SESSION['csrf_token'] = 'old-token';
    $authenticatedId = session_id();
    admin_logout();
    auth_assert($_SESSION === [] && !admin_can('news'), 'Logout clears authentication and old CSRF token');
    auth_assert(session_status() === PHP_SESSION_NONE && session_id() === '' && !is_file($sessionPath . '/sess_' . $authenticatedId), 'Logout destroys session storage and clears session ID');
    session_id($authenticatedId);
    session_start();
    auth_assert(session_id() !== $authenticatedId && !admin_is_logged_in(), 'Replaying pre-logout session ID cannot authenticate');
    expect_login_redirect();

    auth_assert(admin_login('editor-test', $password), 'Login before logout logging failure');
    $authenticatedId = session_id();
    $testDatabase->connectionThrows = true;
    admin_logout();
    $testDatabase->connectionThrows = false;
    auth_assert($_SESSION === [] && session_status() === PHP_SESSION_NONE && !is_file($sessionPath . '/sess_' . $authenticatedId), 'Database logging failure cannot prevent session destruction');
    admin_logout();
    auth_assert($_SESSION === [] && session_status() === PHP_SESSION_NONE, 'Repeated logout does not recreate session');
    echo 'Passed: ' . $checks . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
    ob_end_flush();
}
exit($exitCode);
