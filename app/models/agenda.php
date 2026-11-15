<?php

function agenda_items(int $limit = 0): array
{
    $pdo = db();
    if (!$pdo) {
        return array_map(static fn(array $item): array => [
            'title' => $item['title'],
            'category' => 'Agenda Sekolah',
            'location' => 'Sekolah',
            'description' => $item['detail'],
            'date_label' => $item['date'],
            'starts_at' => null,
            'ends_at' => null,
        ], seed_content()['schoolAgenda'] ?? []);
    }

    ensure_academic_year_columns();
    $statementSql = 'SELECT * FROM agenda_items WHERE is_active = 1 AND academic_year = :academic_year ORDER BY starts_at ASC, sort_order ASC, id DESC';
    if ($limit > 0) {
        $statementSql .= ' LIMIT ' . (int) $limit;
    }

    $statement = $pdo->prepare($statementSql);
    $statement->execute(['academic_year' => current_academic_year()]);
    return $statement->fetchAll();
}

function upcoming_agenda_items(int $limit = 3): array
{
    $pdo = db();
    if (!$pdo) {
        return array_slice(agenda_items(), 0, max(1, $limit));
    }

    ensure_academic_year_columns();
    $statement = $pdo->prepare('SELECT * FROM agenda_items WHERE is_active = 1 AND academic_year = :academic_year AND starts_at >= NOW() ORDER BY starts_at ASC, sort_order ASC, id DESC LIMIT ' . max(1, (int) $limit));
    $statement->execute(['academic_year' => current_academic_year()]);
    $rows = $statement->fetchAll();

    return $rows ?: agenda_items($limit);
}

function admin_agenda_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM agenda_items ORDER BY academic_year DESC, is_active DESC, starts_at ASC, sort_order ASC, id DESC')->fetchAll();
}

function admin_agenda_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM agenda_items WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_agenda_item(array $data): void
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        ensure_academic_year_columns();
        $statement = $pdo->prepare('UPDATE agenda_items SET title = :title, category = :category, location = :location, description = :description, starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active, academic_year = :academic_year, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);

        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $statement = $pdo->prepare('INSERT INTO agenda_items (title, category, location, description, starts_at, ends_at, is_active, academic_year, sort_order) VALUES (:title, :category, :location, :description, :starts_at, :ends_at, :is_active, :academic_year, :sort_order)');
    $statement->execute($data);
}

function delete_agenda_item(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $statement = $pdo->prepare('DELETE FROM agenda_items WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }

        return true;
    }
    return false;
}

function agenda_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['schoolAgenda'] ?? []);
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM agenda_items WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM agenda_items')->fetchColumn();
}
