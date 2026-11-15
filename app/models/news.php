<?php

function ensure_news_publication_column(): void
{
    static $done = false;
    $pdo = db();
    if ($done || !$pdo) {
        return;
    }

    require_schema_columns($pdo, 'news', ['is_active']);

    $done = true;
}

function news_items(): array
{
    $pdo = db();
    if (!$pdo) {
        return seed_content()['latestNews'];
    }

    ensure_news_publication_column();
    $rows = $pdo->query('SELECT * FROM news WHERE is_active = 1 ORDER BY published_at DESC, id DESC')->fetchAll();
    if (!$rows) {
        return seed_content()['latestNews'];
    }

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'date' => date('d F Y', strtotime($row['published_at'])),
            'category' => $row['category'],
            'desc' => $row['excerpt'],
            'image_path' => $row['image_path'] ?: '/public/logo-sekolah.png',
            'content' => preg_split('/\R\R+/', trim($row['content'])) ?: [$row['content']],
        ];
    }, $rows);
}

function news_item_by_slug(string $slug): ?array
{
    foreach (news_items() as $item) {
        if ($item['slug'] === $slug) {
            return $item;
        }
    }
    return null;
}

function admin_news_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_news_publication_column();
    return $pdo->query('SELECT * FROM news ORDER BY is_active DESC, published_at DESC, id DESC')->fetchAll();
}

function news_count(bool $activeOnly = true): int
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['latestNews'] ?? []);
    }

    ensure_news_publication_column();
    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM news WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
}

function related_news_items(string $currentSlug, string $category, int $limit = 3): array
{
    $items = array_values(array_filter(news_items(), static function (array $item) use ($currentSlug, $category): bool {
        return ($item['slug'] ?? '') !== $currentSlug && ($item['category'] ?? '') === $category;
    }));

    if (count($items) < $limit) {
        $existingSlugs = array_column($items, 'slug');
        foreach (news_items() as $item) {
            if (($item['slug'] ?? '') === $currentSlug || in_array($item['slug'] ?? '', $existingSlugs, true)) {
                continue;
            }
            $items[] = $item;
            $existingSlugs[] = $item['slug'] ?? '';
            if (count($items) >= $limit) {
                break;
            }
        }
    }

    return array_slice($items, 0, $limit);
}

function admin_news_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    ensure_news_publication_column();
    $statement = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_news(array $data): void
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
        ensure_news_publication_column();
        execute_media_mutation($pdo, 'UPDATE news SET slug = :slug, title = :title, category = :category, excerpt = :excerpt, content = :content, image_path = :image_path, published_at = :published_at, is_active = :is_active WHERE id = :id', $data, 'news', 'image_path');

        return;
    }

    unset($data['id']);
    ensure_news_publication_column();
    execute_media_mutation($pdo, 'INSERT INTO news (slug, title, category, excerpt, content, image_path, published_at, is_active) VALUES (:slug, :title, :category, :excerpt, :content, :image_path, :published_at, :is_active)', $data, 'news', 'image_path');
}

function delete_news(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        execute_media_mutation($pdo, 'DELETE FROM news WHERE id = :id', ['id' => $id], 'news', 'image_path');

        return true;
    }
    return false;
}
