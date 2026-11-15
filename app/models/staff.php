<?php

function staff_members(): array
{
    $pdo = db();
    if (!$pdo) {
        return seed_content()['staffMembers'] ?? [];
    }

    $rows = $pdo->query('SELECT * FROM staff_members WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
    if (!$rows) {
        return seed_content()['staffMembers'] ?? [];
    }

    return $rows;
}

function admin_staff_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM staff_members ORDER BY is_active DESC, sort_order ASC, name ASC')->fetchAll() : [];
}

function admin_staff_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM staff_members WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_staff_member(array $data): void
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
        execute_media_mutation($pdo, 'UPDATE staff_members SET name = :name, position = :position, subject = :subject, bio = :bio, phone = :phone, email = :email, photo_path = :photo_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id', $data, 'staff_members', 'photo_path');
        log_admin_activity('update', 'staff', 'Data guru/staf diperbarui: ' . $data['name'], 'staff_member', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $savedId = execute_media_mutation($pdo, 'INSERT INTO staff_members (name, position, subject, bio, phone, email, photo_path, is_active, sort_order) VALUES (:name, :position, :subject, :bio, :phone, :email, :photo_path, :is_active, :sort_order)', $data, 'staff_members', 'photo_path');
    log_admin_activity('create', 'staff', 'Data guru/staf dibuat: ' . $data['name'], 'staff_member', $savedId);
}

function delete_staff_member(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_staff_row($id);
        execute_media_mutation($pdo, 'DELETE FROM staff_members WHERE id = :id', ['id' => $id], 'staff_members', 'photo_path');
        log_admin_activity('delete', 'staff', 'Data guru/staf dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'staff_member', $id);
        return true;
    }
    return false;
}

function staff_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['staffMembers'] ?? []);
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM staff_members WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM staff_members')->fetchColumn();
}
