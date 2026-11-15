<?php

function school_facilities(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return array_map(static fn(string $item): array => [
            'name' => $item,
            'category' => 'Fasilitas',
            'location' => null,
            'capacity' => null,
            'condition_status' => 'Baik',
            'description' => $item,
            'image_path' => '/public/logo-sekolah.png',
        ], seed_content()['schoolFacilities'] ?? []);
    }

    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM school_facilities WHERE is_active = 1 AND category = :category ORDER BY sort_order ASC, name ASC');
        $statement->execute(['category' => $category]);
        return $statement->fetchAll();
    }

    $rows = $pdo->query('SELECT * FROM school_facilities WHERE is_active = 1 ORDER BY category ASC, sort_order ASC, name ASC')->fetchAll();
    if (!$rows) {
        return array_map(static fn(string $item): array => [
            'name' => $item,
            'category' => 'Fasilitas',
            'location' => null,
            'capacity' => null,
            'condition_status' => 'Baik',
            'description' => $item,
            'image_path' => '/public/logo-sekolah.png',
        ], seed_content()['schoolFacilities'] ?? []);
    }

    return $rows;
}

function facility_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = $pdo->query('SELECT DISTINCT category FROM school_facilities WHERE is_active = 1 ORDER BY category ASC')->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_facility_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM school_facilities ORDER BY is_active DESC, category ASC, sort_order ASC, name ASC')->fetchAll() : [];
}

function admin_facility_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM school_facilities WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_facility(array $data): void
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
        execute_media_mutation($pdo, 'UPDATE school_facilities SET name = :name, category = :category, location = :location, capacity = :capacity, condition_status = :condition_status, description = :description, image_path = :image_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id', $data, 'school_facilities', 'image_path');

        return;
    }

    unset($data['id']);
    execute_media_mutation($pdo, 'INSERT INTO school_facilities (name, category, location, capacity, condition_status, description, image_path, is_active, sort_order) VALUES (:name, :category, :location, :capacity, :condition_status, :description, :image_path, :is_active, :sort_order)', $data, 'school_facilities', 'image_path');
}

function delete_facility(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        execute_media_mutation($pdo, 'DELETE FROM school_facilities WHERE id = :id', ['id' => $id], 'school_facilities', 'image_path');

        return true;
    }
    return false;
}

function facility_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['schoolFacilities'] ?? []);
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM school_facilities WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM school_facilities')->fetchColumn();
}
