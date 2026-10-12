<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

final class SchemaReadOnlyStatement extends PDOStatement
{
    public function __construct(private array $rows) {}
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->rows; }
}

final class SchemaReadOnlyDatabase extends PDO
{
    public array $columns = [];
    public array $queries = [];
    public bool $fail = false;
    public function __construct() {}
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queries[] = $query;
        if ($this->fail) {
            throw new PDOException('Synthetic private database error');
        }
        if (!preg_match('/\ASHOW COLUMNS FROM ([a-z_]+)\z/', $query, $match)) {
            throw new LogicException('Unexpected non-metadata query');
        }
        return new SchemaReadOnlyStatement(array_map(static fn($name) => ['Field'=>$name], $this->columns[$match[1]] ?? []));
    }
    public function exec(string $statement): int|false { throw new LogicException('Runtime must not execute DDL'); }
    public function prepare(string $query, array $options = []): PDOStatement|false { throw new LogicException('Schema check must not backfill data'); }
}

$checks = 0;
function schema_check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
function db(): ?PDO { global $database; return $database; }

try {
    $runtime = ($argv[1] ?? '') === '--runtime';
    if ($runtime) {
        require __DIR__ . '/stage3_database_preflight.php';
        $database = stage3_test_database();
    } else {
        $database = new SchemaReadOnlyDatabase();
    }
    require __DIR__ . '/../includes/auth.php';
    require __DIR__ . '/../includes/repositories.php';
    $requirements = [
        'admins'=>['full_name','role','is_active'],
        'news'=>['is_active'],
        'agenda_items'=>['academic_year'], 'school_documents'=>['academic_year'], 'school_achievements'=>['academic_year'],
        'admin_activity_logs'=>['id','admin_id','username','role','action','module','entity_type','entity_id','description','metadata','ip_address','user_agent','created_at'],
    ];
    $helpers = ['ensure_admin_role_columns', 'ensure_news_publication_column', 'ensure_academic_year_columns', 'ensure_admin_activity_logs_table'];
    if ($runtime) {
        $snapshot = static function () use ($database): array {
            $state = [];
            foreach ($database->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
                $quoted = '`' . str_replace('`', '``', $table) . '`';
                $state[$table] = [$database->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM)[1],
                    $database->query('SELECT * FROM ' . $quoted)->fetchAll()];
            }
            return $state;
        };
        $before = $snapshot();
        foreach ($helpers as $helper) { $helper(); schema_check(true, $helper . ' accepts complete test schema'); }
        schema_check($before === $snapshot(), 'Actual testing schema and data unchanged');
    } else {
        foreach ($requirements as $table=>$columns) {
            foreach ($columns as $missing) {
                $database->columns = $requirements;
                $database->columns[$table] = array_values(array_diff($columns, [$missing]));
                try {
                    require_schema_columns($database, $table, $columns);
                    throw new LogicException('Missing column accepted');
                } catch (RuntimeException $exception) {
                    schema_check($exception->getMessage() === 'Struktur penyimpanan belum kompatibel. Hubungi pengelola sistem.', 'Missing column rejected generically');
                }
            }
        }
        $database->columns = [];
        foreach ($helpers as $helper) {
            try { $helper(); throw new LogicException('Missing schema accepted'); }
            catch (RuntimeException $exception) { schema_check(!str_contains($exception->getMessage(), 'Synthetic'), 'Missing schema rejected without mutation'); }
        }
        $database->columns = $requirements;
        $database->fail = true;
        foreach ($helpers as $helper) {
            try { $helper(); throw new LogicException('Database error accepted'); }
            catch (PDOException $exception) { schema_check(true, 'Verification error propagated, not assumed compatible'); }
        }
        $database->fail = false;
        foreach ($helpers as $helper) { $helper(); schema_check(true, 'Compatible schema accepted'); }
        foreach ($database->queries as $query) { schema_check(str_starts_with($query, 'SHOW COLUMNS FROM '), 'Only read-only metadata SQL issued'); }
        foreach (['auth.php','repositories.php'] as $file) {
            $source = file_get_contents(__DIR__ . '/../includes/' . $file);
            schema_check(!preg_match('/\b(?:CREATE|ALTER|DROP)\s+TABLE\b/i', $source), 'No runtime DDL in ' . $file);
            schema_check(!str_contains($source, 'WHERE academic_year = "" OR academic_year IS NULL'), 'No runtime year backfill in ' . $file);
        }
    }
    echo 'PASS: ' . $checks . ' schema stability checks' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: Schema stability test (' . get_class($exception) . ').' . PHP_EOL);
    exit(1);
}
