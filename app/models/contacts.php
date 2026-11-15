<?php

function save_contact_message(array $data): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $statement = $pdo->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)');
    $statement->execute([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'subject' => $data['subject'],
        'message' => $data['message'],
    ]);
}

function admin_contact_messages(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC')->fetchAll() : [];
}

function update_contact_message_status(mixed $id, string $status): void
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $allowed = ['baru', 'diproses', 'selesai'];
    if (!in_array($status, $allowed, true)) {
        return;
    }

    $statement = $pdo->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
    $statement->execute([
        'id' => $id,
        'status' => $status,
    ]);
}

function delete_contact_message(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $statement = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }

        return true;
    }
    return false;
}

function contact_message_count(?string $status = null): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if ($status === null) {
        return (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM contact_messages WHERE status = :status');
    $statement->execute(['status' => $status]);
    return (int) $statement->fetchColumn();
}
