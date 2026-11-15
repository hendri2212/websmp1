<?php

function achievements(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM school_achievements WHERE is_active = 1 AND academic_year = :academic_year AND category = :category ORDER BY achievement_date DESC, sort_order ASC, id DESC');
        $statement->execute(['academic_year' => current_academic_year(), 'category' => $category]);
        return $statement->fetchAll();
    }

    $statement = $pdo->prepare('SELECT * FROM school_achievements WHERE is_active = 1 AND academic_year = :academic_year ORDER BY achievement_date DESC, sort_order ASC, id DESC');
    $statement->execute(['academic_year' => current_academic_year()]);
    return $statement->fetchAll();
}

function home_achievements(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM school_achievements WHERE is_active = 1 ORDER BY achievement_date DESC, sort_order ASC, id DESC')->fetchAll();
}

function achievement_rows_all_years(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM school_achievements WHERE is_active = 1 AND category = :category ORDER BY achievement_date DESC, sort_order ASC, id DESC');
        $statement->execute(['category' => $category]);
        return $statement->fetchAll();
    }

    return $pdo->query('SELECT * FROM school_achievements WHERE is_active = 1 ORDER BY achievement_date DESC, sort_order ASC, id DESC')->fetchAll();
}

function achievement_public_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    ensure_academic_year_columns();
    $statement = $pdo->prepare('SELECT * FROM school_achievements WHERE id = :id AND is_active = 1 LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function related_achievements(int $currentId, string $category, int $limit = 3): array
{
    $items = array_values(array_filter(achievement_rows_all_years($category), static function (array $item) use ($currentId): bool {
        return (int) ($item['id'] ?? 0) !== $currentId;
    }));

    if (count($items) < $limit) {
        $existingIds = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $items);
        foreach (achievement_rows_all_years() as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id === $currentId || in_array($id, $existingIds, true)) {
                continue;
            }
            $items[] = $item;
            $existingIds[] = $id;
            if (count($items) >= $limit) {
                break;
            }
        }
    }

    return array_slice($items, 0, $limit);
}

function achievement_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    $statement = $pdo->prepare('SELECT DISTINCT category FROM school_achievements WHERE is_active = 1 AND academic_year = :academic_year ORDER BY category ASC');
    $statement->execute(['academic_year' => current_academic_year()]);
    $rows = $statement->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function achievement_categories_all_years(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    ensure_academic_year_columns();
    $rows = $pdo->query('SELECT DISTINCT category FROM school_achievements WHERE is_active = 1 ORDER BY category ASC')->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_achievement_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM school_achievements ORDER BY academic_year DESC, is_active DESC, achievement_date DESC, sort_order ASC, id DESC')->fetchAll();
}

function admin_achievement_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM school_achievements WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_achievement(array $data): void
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
        execute_media_mutation($pdo, 'UPDATE school_achievements SET title = :title, level = :level, category = :category, achiever_name = :achiever_name, achievement_date = :achievement_date, description = :description, image_path = :image_path, is_active = :is_active, academic_year = :academic_year, sort_order = :sort_order WHERE id = :id', $data, 'school_achievements', 'image_path');
        log_admin_activity('update', 'achievements', 'Prestasi diperbarui: ' . $data['title'], 'achievement', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $savedId = execute_media_mutation($pdo, 'INSERT INTO school_achievements (title, level, category, achiever_name, achievement_date, description, image_path, is_active, academic_year, sort_order) VALUES (:title, :level, :category, :achiever_name, :achievement_date, :description, :image_path, :is_active, :academic_year, :sort_order)', $data, 'school_achievements', 'image_path');
    log_admin_activity('create', 'achievements', 'Prestasi dibuat: ' . $data['title'], 'achievement', $savedId);
}

function delete_achievement(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_achievement_row($id);
        execute_media_mutation($pdo, 'DELETE FROM school_achievements WHERE id = :id', ['id' => $id], 'school_achievements', 'image_path');
        log_admin_activity('delete', 'achievements', 'Prestasi dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'achievement', $id);
        return true;
    }
    return false;
}

function achievement_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM school_achievements WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM school_achievements')->fetchColumn();
}
