<?php

function public_documents(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM school_documents WHERE is_active = 1 AND academic_year = :academic_year AND category = :category ORDER BY sort_order ASC, created_at DESC, id DESC');
        $statement->execute(['academic_year' => current_academic_year(), 'category' => $category]);
        return $statement->fetchAll();
    }

    $statement = $pdo->prepare('SELECT * FROM school_documents WHERE is_active = 1 AND academic_year = :academic_year ORDER BY category ASC, sort_order ASC, created_at DESC, id DESC');
    $statement->execute(['academic_year' => current_academic_year()]);
    return $statement->fetchAll();
}

function document_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    $statement = $pdo->prepare('SELECT DISTINCT category FROM school_documents WHERE is_active = 1 AND academic_year = :academic_year ORDER BY category ASC');
    $statement->execute(['academic_year' => current_academic_year()]);
    $rows = $statement->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_document_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM school_documents ORDER BY academic_year DESC, is_active DESC, category ASC, sort_order ASC, created_at DESC, id DESC')->fetchAll();
}

function admin_document_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM school_documents WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function public_document_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM school_documents WHERE id = :id AND is_active = 1 LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_school_document(array $data): void
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
        ensure_academic_year_columns();
        execute_media_mutation($pdo, 'UPDATE school_documents SET title = :title, category = :category, description = :description, file_path = :file_path, original_filename = :original_filename, file_size = :file_size, is_active = :is_active, academic_year = :academic_year, sort_order = :sort_order WHERE id = :id', $data, 'school_documents', 'file_path');

        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    execute_media_mutation($pdo, 'INSERT INTO school_documents (title, category, description, file_path, original_filename, file_size, is_active, academic_year, sort_order) VALUES (:title, :category, :description, :file_path, :original_filename, :file_size, :is_active, :academic_year, :sort_order)', $data, 'school_documents', 'file_path');
}

function delete_school_document(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        execute_media_mutation($pdo, 'DELETE FROM school_documents WHERE id = :id', ['id' => $id], 'school_documents', 'file_path');

        return true;
    }
    return false;
}

function increment_document_download_count(mixed $id): void
{
    $id = require_database_id($id);
    $pdo = db();
    if ($pdo) {
        $statement = $pdo->prepare('UPDATE school_documents SET download_count = download_count + 1 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}

function document_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM school_documents WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM school_documents')->fetchColumn();
}
