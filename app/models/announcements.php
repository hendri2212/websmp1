<?php

function active_announcements(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY sort_order ASC, created_at DESC, id DESC");
    return $statement->fetchAll();
}

function admin_announcement_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM announcements ORDER BY is_active DESC, sort_order ASC, created_at DESC, id DESC')->fetchAll() : [];
}

function admin_announcement_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_announcement(array $data): void
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE announcements SET title = :title, content = :content, link_label = :link_label, link_url = :link_url, starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'announcements', 'Pengumuman diperbarui: ' . $data['title'], 'announcement', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO announcements (title, content, link_label, link_url, starts_at, ends_at, is_active, sort_order) VALUES (:title, :content, :link_label, :link_url, :starts_at, :ends_at, :is_active, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'announcements', 'Pengumuman dibuat: ' . $data['title'], 'announcement', (int) $pdo->lastInsertId());
}

function delete_announcement(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_announcement_row($id);
        $statement = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }
        log_admin_activity('delete', 'announcements', 'Pengumuman dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'announcement', $id);
        return true;
    }
    return false;
}

function announcement_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if (!$activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
    }

    return (int) $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())")->fetchColumn();
}
