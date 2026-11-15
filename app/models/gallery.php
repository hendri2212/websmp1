<?php

function gallery_items(): array
{
    $pdo = db();
    if (!$pdo) {
        return seed_content()['galleryItems'];
    }

    $rows = $pdo->query('SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC')->fetchAll();
    if (!$rows) {
        return seed_content()['galleryItems'];
    }

    return array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'image' => $row['image_path'],
    ], $rows);
}

function admin_gallery_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC')->fetchAll() : [];
}

function gallery_count(): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['galleryItems'] ?? []);
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
}

function admin_gallery_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $statement = $pdo->prepare('SELECT * FROM gallery_items WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_gallery_item(array $data): void
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
        execute_media_mutation($pdo, 'UPDATE gallery_items SET title = :title, description = :description, image_path = :image_path, sort_order = :sort_order WHERE id = :id', $data, 'gallery_items', 'image_path');

        return;
    }

    unset($data['id']);
    execute_media_mutation($pdo, 'INSERT INTO gallery_items (title, description, image_path, sort_order) VALUES (:title, :description, :image_path, :sort_order)', $data, 'gallery_items', 'image_path');
}

function delete_gallery_item(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        execute_media_mutation($pdo, 'DELETE FROM gallery_items WHERE id = :id', ['id' => $id], 'gallery_items', 'image_path');

        return true;
    }
    return false;
}
