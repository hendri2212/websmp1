<?php

declare(strict_types=1);

abstract class Seeder
{
    abstract public function run(PDO $pdo): int;

    protected function seedRows(PDO $pdo, string $table, array $rows, array $identity): int
    {
        $inserted = 0;
        foreach ($rows as $row) {
            $inserted += (int) $this->insertMissing($pdo, $table, $row, $identity);
        }
        return $inserted;
    }

    protected function insertMissing(PDO $pdo, string $table, array $data, array $identity): bool
    {
        $where = implode(' AND ', array_map(static fn(string $column): string => "`$column` = ?", $identity));
        $check = $pdo->prepare("SELECT 1 FROM `$table` WHERE $where LIMIT 1");
        $check->execute(array_map(static fn(string $column): mixed => $data[$column], $identity));
        if ($check->fetchColumn() !== false) {
            return false;
        }
        $columns = '`' . implode('`, `', array_keys($data)) . '`';
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $statement = $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
        $statement->execute(array_values($data));
        return true;
    }
}
