<?php

// Loaded only by the private testing router, never by the application.
if (PHP_SAPI !== 'cli-server' || !defined('STAGE5_FAILURE_TEST')) {
    http_response_code(403);
    exit;
}
final class FailureQueryDatabase extends PDO
{
    public function __construct(private PDO $connection, private string $scenario) {}
    private function failureSql(): string
    {
        return $this->scenario === 'missing-table'
            ? 'SELECT * FROM stage5_missing_table_fixture WHERE 1=0'
            : 'SELECT STAGE5_PRIVATE_MISSING_COLUMN FROM admins WHERE 1=0';
    }
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        return $this->connection->query($this->failureSql());
    }
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        preg_match_all('/:[a-z_]+/i', $query, $matches);
        $sql = $this->failureSql();
        foreach (array_unique($matches[0]) as $parameter) {
            $sql .= ' AND ' . $parameter . ' IS NOT NULL';
        }
        return $this->connection->prepare($sql, $options);
    }
}
function db(): ?PDO
{
    $connection = stage5_actual_db();
    $scenario = $GLOBALS['stage5_scenario'] ?? '';
    return $connection && in_array($scenario, ['query', 'missing-table'], true)
        ? new FailureQueryDatabase($connection, $scenario) : $connection;
}
