<?php

function extracurriculars(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return array_map(static fn(array $item): array => [
            'name' => $item['name'],
            'category' => 'Umum',
            'coach' => null,
            'schedule_text' => null,
            'location' => null,
            'description' => $item['desc'],
            'image_path' => '/public/logo-sekolah.png',
        ], seed_content()['extracurriculars'] ?? []);
    }

    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM extracurriculars WHERE is_active = 1 AND category = :category ORDER BY sort_order ASC, name ASC');
        $statement->execute(['category' => $category]);
        return $statement->fetchAll();
    }

    $rows = $pdo->query('SELECT * FROM extracurriculars WHERE is_active = 1 ORDER BY category ASC, sort_order ASC, name ASC')->fetchAll();
    if (!$rows) {
        return array_map(static fn(array $item): array => [
            'name' => $item['name'],
            'category' => 'Umum',
            'coach' => null,
            'schedule_text' => null,
            'location' => null,
            'description' => $item['desc'],
            'image_path' => '/public/logo-sekolah.png',
        ], seed_content()['extracurriculars'] ?? []);
    }

    return $rows;
}

function extracurricular_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = $pdo->query('SELECT DISTINCT category FROM extracurriculars WHERE is_active = 1 ORDER BY category ASC')->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_extracurricular_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM extracurriculars ORDER BY is_active DESC, category ASC, sort_order ASC, name ASC')->fetchAll() : [];
}

function admin_extracurricular_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM extracurriculars WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_extracurricular(array $data): void
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    $pdo = db();
    if (!$pdo) {
        rollback_uploaded_files();
        throw new RuntimeException('Database tidak tersedia. File tidak disimpan.');
    }

    if (!empty($data['id'])) {
        execute_media_mutation($pdo, 'UPDATE extracurriculars SET name = :name, category = :category, coach = :coach, schedule_text = :schedule_text, location = :location, description = :description, image_path = :image_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id', $data, 'extracurriculars', 'image_path');

        return;
    }

    unset($data['id']);
    execute_media_mutation($pdo, 'INSERT INTO extracurriculars (name, category, coach, schedule_text, location, description, image_path, is_active, sort_order) VALUES (:name, :category, :coach, :schedule_text, :location, :description, :image_path, :is_active, :sort_order)', $data, 'extracurriculars', 'image_path');
}

function delete_extracurricular(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        execute_media_mutation($pdo, 'DELETE FROM extracurriculars WHERE id = :id', ['id' => $id], 'extracurriculars', 'image_path');

        return true;
    }
    return false;
}

function extracurricular_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['extracurriculars'] ?? []);
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM extracurriculars WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM extracurriculars')->fetchColumn();
}
