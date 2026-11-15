<?php

function faqs(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM faqs WHERE is_active = 1 AND category = :category ORDER BY sort_order ASC, id DESC');
        $statement->execute(['category' => $category]);
        return $statement->fetchAll();
    }

    return $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY category ASC, sort_order ASC, id DESC')->fetchAll();
}

function faq_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = $pdo->query('SELECT DISTINCT category FROM faqs WHERE is_active = 1 ORDER BY category ASC')->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_faq_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM faqs ORDER BY is_active DESC, category ASC, sort_order ASC, id DESC')->fetchAll() : [];
}

function admin_faq_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM faqs WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_faq(array $data): void
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE faqs SET question = :question, answer = :answer, category = :category, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);

        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO faqs (question, answer, category, is_active, sort_order) VALUES (:question, :answer, :category, :is_active, :sort_order)');
    $statement->execute($data);
}

function delete_faq(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $statement = $pdo->prepare('DELETE FROM faqs WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }

        return true;
    }
    return false;
}

function faq_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM faqs WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
}
