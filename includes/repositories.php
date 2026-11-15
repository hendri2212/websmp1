<?php

require_once __DIR__ . '/schema_requirements.php';

function seed_content(): array
{
    static $seed;
    if ($seed === null) {
        $seed = require __DIR__ . '/../config/site_data.php';
    }
    return $seed;
}

function site_content(): array
{
    $content = seed_content();
    $pdo = db();

    if (!$pdo) {
        return $content;
    }

    $keys = ['schoolProfile', 'siteMaintenance', 'siteTheme', 'siteAcademic', 'siteHome', 'navigation', 'stats', 'quickLinks', 'featuredAreas', 'excellencePrograms', 'schoolHighlights', 'schoolAgenda', 'profileSections', 'principalMessage', 'schoolIdentity', 'schoolAccreditation', 'schoolObjectives', 'schoolFacilities', 'academicPrograms', 'academicServices', 'academicSupportItems', 'academicCommitments', 'staffMembers', 'studentPrograms', 'studentDevelopmentAreas', 'extracurriculars', 'studentGuidancePoints', 'galleryHighlights', 'ppdbSteps', 'ppdbRequirements', 'ppdbInformation', 'ppdbCriteria', 'ppdbNotes', 'contactChannels', 'contactServices', 'contactNotes'];
    $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    foreach ($keys as $key) {
        if (!empty($settings[$key])) {
            $decoded = json_decode($settings[$key], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content[$key] = $decoded;
            }
        }
    }

    $content['schoolProfile']['logoPath'] = normalized_school_logo_path($content['schoolProfile']['logoPath'] ?? null);
    $content['navigation'] = normalized_navigation($content['navigation'] ?? [], seed_content()['navigation'] ?? []);
    $content['siteAcademic'] = normalized_site_academic($content['siteAcademic'] ?? [], seed_content()['siteAcademic'] ?? []);
    $content['siteHome'] = normalized_site_home($content['siteHome'] ?? [], seed_content()['siteHome'] ?? []);

    return $content;
}

function normalized_school_logo_path(mixed $path): string
{
    $path = is_string($path) ? '/' . ltrim(trim($path), '/') : '';
    if (!str_starts_with($path, '/public/')
        || !file_is_within_directory(__DIR__ . '/..' . $path, __DIR__ . '/../public')) {
        return '/public/logo-sekolah.png';
    }

    return $path;
}

function normalized_site_academic(array $settings, array $defaults): array
{
    $currentYear = trim((string) ($settings['currentYear'] ?? $defaults['currentYear'] ?? '2025/2026'));
    return ['currentYear' => $currentYear !== '' ? $currentYear : '2025/2026'];
}

function current_academic_year(): string
{
    return site_content()['siteAcademic']['currentYear'] ?? '2025/2026';
}

function repository_quote_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function ensure_academic_year_columns(): void
{
    static $done = false;
    $pdo = db();
    if ($done || !$pdo) {
        return;
    }

    $tables = ['agenda_items', 'school_documents', 'school_achievements'];
    foreach ($tables as $table) {
        require_schema_columns($pdo, $table, ['academic_year']);
    }

    $done = true;
}

function normalized_site_home(array $settings, array $defaults): array
{
    $normalized = array_replace($defaults, $settings);
    foreach (['showAnnouncements', 'showQuickLinks', 'showLogoCard', 'showStats', 'showPrincipalCard', 'showTeacherCarousel', 'showProfileIntro', 'showFeaturedAreas', 'showAgenda'] as $key) {
        $normalized[$key] = array_key_exists($key, $normalized) ? (bool) $normalized[$key] : true;
    }

    foreach (['heroTitle', 'heroSubtitle', 'primaryButtonLabel', 'primaryButtonUrl', 'secondaryButtonLabel', 'secondaryButtonUrl', 'profileVideoUrl', 'profileVideoTitle', 'profileVideoDescription'] as $key) {
        $normalized[$key] = trim((string) ($normalized[$key] ?? ($defaults[$key] ?? '')));
    }
    $normalized['teacherLimit'] = max(4, min(24, (int) ($normalized['teacherLimit'] ?? 12)));
    $normalized['agendaLimit'] = max(1, min(9, (int) ($normalized['agendaLimit'] ?? 3)));

    return $normalized;
}

function normalized_navigation(array $navigation, array $defaults): array
{
    $currentByHref = [];
    foreach ($navigation as $item) {
        if (!empty($item['href'])) {
            $currentByHref[$item['href']] = $item;
        }
    }

    $normalized = [];
    foreach ($defaults as $default) {
        $current = $currentByHref[$default['href']] ?? [];
        $normalized[] = [
            'href' => $default['href'],
            'label' => trim($current['label'] ?? $default['label']),
            'visible' => array_key_exists('visible', $current) ? (bool) $current['visible'] : (bool) ($default['visible'] ?? true),
        ];
    }

    return $normalized;
}

function save_site_sections(array $payload): void
{
    $pdo = db();
    if (!$pdo) {
        rollback_uploaded_files();
        throw new RuntimeException('Database tidak tersedia. File tidak disimpan.');
    }

    media_lifecycle_lock();
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Operasi file memerlukan transaksi tersendiri.');
    }
    $commitAttempted = false;
    try {
        require_transactional_media_table($pdo, 'site_settings');
        $pdo->beginTransaction();
        $oldSettings = $pdo->query('SELECT setting_key, setting_value FROM site_settings FOR UPDATE')->fetchAll();
        foreach ($oldSettings as $setting) {
            if (array_key_exists($setting['setting_key'], $payload)) {
                $oldPaths = media_paths_in_value(json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR));
                $newPaths = media_paths_in_value($payload[$setting['setting_key']]);
                foreach (array_diff($newPaths, $oldPaths) as $new) {
                    require_new_media_reference($new);
                }
                foreach (array_diff($oldPaths, $newPaths) as $old) {
                    media_cleanup_enqueue($old);
                }
            }
        }
        $existingKeys = array_column($oldSettings, 'setting_key');
        foreach ($payload as $key => $value) {
            if (!in_array($key, $existingKeys, true)) {
                require_new_media_reference($value);
            }
        }
        require_pending_media_files();
        $statement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($payload as $key => $value) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            ]);
        }
        require_pending_media_files();
        $commitAttempted = true;
        $GLOBALS['media_commit_in_progress'] = true;
        $pdo->commit();
    } catch (Throwable $exception) {
        $rollbackConfirmed = true;
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $rollbackException) {
            $rollbackConfirmed = false;
            error_log('Settings transaction outcome could not be verified; media retained for safe retry.');
        }
        if ($commitAttempted || !$rollbackConfirmed) {
            $GLOBALS['pending_uploaded_files'] = [];
        } else {
            rollback_uploaded_files();
        }
        $GLOBALS['media_commit_in_progress'] = false;
        throw $exception;
    }

    commit_uploaded_files();
    retry_media_cleanup($pdo);
    log_admin_activity('update', 'settings', 'Pengaturan website diperbarui.', 'site_settings');
}

function admin_activity_logs(array $filters = [], int $limit = 200): array
{
    ensure_admin_activity_logs_table();
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(username LIKE :q OR module LIKE :q OR action LIKE :q OR description LIKE :q OR ip_address LIKE :q)';
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['module'])) {
        $where[] = 'module = :module';
        $params['module'] = $filters['module'];
    }
    if (!empty($filters['action'])) {
        $where[] = 'action = :action';
        $params['action'] = $filters['action'];
    }

    $sql = 'SELECT * FROM admin_activity_logs';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(500, $limit));

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function admin_activity_log_options(string $field): array
{
    ensure_admin_activity_logs_table();
    $pdo = db();
    if (!$pdo || !in_array($field, ['module', 'action'], true)) {
        return [];
    }

    $rows = $pdo->query('SELECT DISTINCT ' . $field . ' FROM admin_activity_logs ORDER BY ' . $field . ' ASC')->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_filter($rows));
}

function admin_users(): array
{
    ensure_admin_role_columns();
    $pdo = db();
    return $pdo ? $pdo->query('SELECT id, username, full_name, role, is_active, created_at FROM admins ORDER BY role ASC, username ASC')->fetchAll() : [];
}

function admin_user(mixed $id): ?array
{
    $id = require_database_id($id);
    ensure_admin_role_columns();
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, username, full_name, role, is_active, created_at FROM admins WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function admin_account_transaction(PDO $pdo, callable $operation): array
{
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Operasi akun memerlukan transaksi tersendiri.');
    }
    require_transactional_media_table($pdo, 'admins');
    try {
        $pdo->beginTransaction();
        // Serialize account policy checks with writes, including the last-active-superadmin rule.
        $pdo->query('SELECT id FROM admins ORDER BY id FOR UPDATE')->fetchAll();
        $result = $operation();
        if ($result['success'] ?? false) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
        return $result;
    } catch (Throwable $exception) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $rollbackException) {
            error_log('Account transaction outcome could not be verified; manual verification required.');
        }
        throw $exception;
    }
}

function save_admin_user(array $data): array
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    ensure_admin_role_columns();
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database belum terhubung.'];
    }

    $allowedRoles = ['superadmin', 'editor', 'ppdb'];
    if (!in_array($data['role'], $allowedRoles, true)) {
        return ['success' => false, 'message' => 'Role admin tidak valid.'];
    }

    return admin_account_transaction($pdo, static fn(): array => save_admin_user_locked($pdo, $data));
}

function save_admin_user_locked(PDO $pdo, array $data): array
{

    if (!empty($data['id'])) {
        $existing = admin_user((int) $data['id']);
        if (!$existing) {
            return ['success' => false, 'message' => 'Akun admin tidak ditemukan.'];
        }
        if ((int) ($_SESSION['admin_id'] ?? 0) === (int) $data['id'] && ((int) $data['is_active'] !== 1 || $data['role'] !== 'superadmin')) {
            return ['success' => false, 'message' => 'Akun yang sedang digunakan tidak bisa dinonaktifkan atau diturunkan rolenya.'];
        }
        if (($existing['role'] ?? '') === 'superadmin' && ((int) $data['is_active'] !== 1 || $data['role'] !== 'superadmin')) {
            $statement = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE role = 'superadmin' AND is_active = 1 AND id <> :id");
            $statement->execute(['id' => (int) $data['id']]);
            if ((int) $statement->fetchColumn() === 0) {
                return ['success' => false, 'message' => 'Minimal harus ada satu superadmin aktif.'];
            }
        }
        $params = [
            'id' => (int) $data['id'],
            'username' => $data['username'],
            'full_name' => $data['full_name'] ?: null,
            'role' => $data['role'],
            'is_active' => (int) $data['is_active'],
        ];
        $sql = 'UPDATE admins SET username = :username, full_name = :full_name, role = :role, is_active = :is_active';
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password baru minimal 8 karakter.'];
            }
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        log_admin_activity('update', 'admins', 'Akun admin diperbarui: ' . $data['username'], 'admin', (int) $data['id']);
        return ['success' => true, 'message' => 'Akun admin berhasil diperbarui.'];
    }

    if (strlen($data['password']) < 8) {
        return ['success' => false, 'message' => 'Password admin baru minimal 8 karakter.'];
    }

    $statement = $pdo->prepare('INSERT INTO admins (username, full_name, role, is_active, password_hash) VALUES (:username, :full_name, :role, :is_active, :password_hash)');
    $statement->execute([
        'username' => $data['username'],
        'full_name' => $data['full_name'] ?: null,
        'role' => $data['role'],
        'is_active' => (int) $data['is_active'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
    ]);
    log_admin_activity('create', 'admins', 'Akun admin dibuat: ' . $data['username'], 'admin', (int) $pdo->lastInsertId());

    return ['success' => true, 'message' => 'Akun admin berhasil dibuat.'];
}

function delete_admin_user(mixed $id): array
{
    $id = require_database_id($id);
    ensure_admin_role_columns();
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Data gagal dihapus. Silakan coba lagi.'];
    }

    return admin_account_transaction($pdo, static fn(): array => delete_admin_user_locked($pdo, $id));
}

function delete_admin_user_locked(PDO $pdo, int $id): array
{

    if ((int) ($_SESSION['admin_id'] ?? 0) === $id) {
        return ['success' => false, 'message' => 'Akun yang sedang login tidak bisa dihapus.'];
    }

    $admin = admin_user($id);
    if (!$admin) {
        return ['success' => false, 'message' => 'Akun admin tidak ditemukan.'];
    }
    if (($admin['role'] ?? '') === 'superadmin' && (int) ($admin['is_active'] ?? 0) === 1) {
        $statement = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE role = 'superadmin' AND is_active = 1 AND id <> :id");
        $statement->execute(['id' => $id]);
        if ((int) $statement->fetchColumn() === 0) {
            return ['success' => false, 'message' => 'Superadmin aktif terakhir tidak bisa dihapus.'];
        }
    }
    $statement = $pdo->prepare('DELETE FROM admins WHERE id = :id');
    $statement->execute(['id' => $id]);
    if ($statement->rowCount() !== 1) {
        return ['success' => false, 'message' => 'Akun admin tidak ditemukan atau sudah dihapus.'];
    }
    log_admin_activity('delete', 'admins', 'Akun admin dihapus: ' . ($admin['username'] ?? ('ID ' . $id)), 'admin', $id);
    return ['success' => true, 'message' => 'Akun admin berhasil dihapus.'];
}

function school_profile_sections(): array
{
    return site_content()['profileSections'] ?? [];
}

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
        log_admin_activity('update', 'news', 'Berita diperbarui: ' . $data['title'], 'news', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_news_publication_column();
    $savedId = execute_media_mutation($pdo, 'INSERT INTO news (slug, title, category, excerpt, content, image_path, published_at, is_active) VALUES (:slug, :title, :category, :excerpt, :content, :image_path, :published_at, :is_active)', $data, 'news', 'image_path');
    log_admin_activity('create', 'news', 'Berita dibuat: ' . $data['title'], 'news', $savedId);
}

function delete_news(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_news_row($id);
        execute_media_mutation($pdo, 'DELETE FROM news WHERE id = :id', ['id' => $id], 'news', 'image_path');
        log_admin_activity('delete', 'news', 'Berita dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'news', $id);
        return true;
    }
    return false;
}

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
        log_admin_activity('update', 'gallery', 'Galeri diperbarui: ' . $data['title'], 'gallery_item', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $savedId = execute_media_mutation($pdo, 'INSERT INTO gallery_items (title, description, image_path, sort_order) VALUES (:title, :description, :image_path, :sort_order)', $data, 'gallery_items', 'image_path');
    log_admin_activity('create', 'gallery', 'Galeri dibuat: ' . $data['title'], 'gallery_item', $savedId);
}

function delete_gallery_item(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_gallery_row($id);
        execute_media_mutation($pdo, 'DELETE FROM gallery_items WHERE id = :id', ['id' => $id], 'gallery_items', 'image_path');
        log_admin_activity('delete', 'gallery', 'Galeri dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'gallery_item', $id);
        return true;
    }
    return false;
}

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
        log_admin_activity('update', 'agenda', 'Agenda diperbarui: ' . $data['title'], 'agenda_item', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $statement = $pdo->prepare('INSERT INTO agenda_items (title, category, location, description, starts_at, ends_at, is_active, academic_year, sort_order) VALUES (:title, :category, :location, :description, :starts_at, :ends_at, :is_active, :academic_year, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'agenda', 'Agenda dibuat: ' . $data['title'], 'agenda_item', (int) $pdo->lastInsertId());
}

function delete_agenda_item(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_agenda_row($id);
        $statement = $pdo->prepare('DELETE FROM agenda_items WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }
        log_admin_activity('delete', 'agenda', 'Agenda dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'agenda_item', $id);
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
        log_admin_activity('update', 'documents', 'Dokumen diperbarui: ' . $data['title'], 'school_document', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $savedId = execute_media_mutation($pdo, 'INSERT INTO school_documents (title, category, description, file_path, original_filename, file_size, is_active, academic_year, sort_order) VALUES (:title, :category, :description, :file_path, :original_filename, :file_size, :is_active, :academic_year, :sort_order)', $data, 'school_documents', 'file_path');
    log_admin_activity('create', 'documents', 'Dokumen dibuat: ' . $data['title'], 'school_document', $savedId);
}

function delete_school_document(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_document_row($id);
        execute_media_mutation($pdo, 'DELETE FROM school_documents WHERE id = :id', ['id' => $id], 'school_documents', 'file_path');
        log_admin_activity('delete', 'documents', 'Dokumen dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'school_document', $id);
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
        log_admin_activity('update', 'extracurriculars', 'Ekstrakurikuler diperbarui: ' . $data['name'], 'extracurricular', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $savedId = execute_media_mutation($pdo, 'INSERT INTO extracurriculars (name, category, coach, schedule_text, location, description, image_path, is_active, sort_order) VALUES (:name, :category, :coach, :schedule_text, :location, :description, :image_path, :is_active, :sort_order)', $data, 'extracurriculars', 'image_path');
    log_admin_activity('create', 'extracurriculars', 'Ekstrakurikuler dibuat: ' . $data['name'], 'extracurricular', $savedId);
}

function delete_extracurricular(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_extracurricular_row($id);
        execute_media_mutation($pdo, 'DELETE FROM extracurriculars WHERE id = :id', ['id' => $id], 'extracurriculars', 'image_path');
        log_admin_activity('delete', 'extracurriculars', 'Ekstrakurikuler dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'extracurricular', $id);
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
        log_admin_activity('update', 'facilities', 'Fasilitas diperbarui: ' . $data['name'], 'facility', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $savedId = execute_media_mutation($pdo, 'INSERT INTO school_facilities (name, category, location, capacity, condition_status, description, image_path, is_active, sort_order) VALUES (:name, :category, :location, :capacity, :condition_status, :description, :image_path, :is_active, :sort_order)', $data, 'school_facilities', 'image_path');
    log_admin_activity('create', 'facilities', 'Fasilitas dibuat: ' . $data['name'], 'facility', $savedId);
}

function delete_facility(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. File tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_facility_row($id);
        execute_media_mutation($pdo, 'DELETE FROM school_facilities WHERE id = :id', ['id' => $id], 'school_facilities', 'image_path');
        log_admin_activity('delete', 'facilities', 'Fasilitas dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'facility', $id);
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

function faqs(?string $category = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    if ($category) {
        $statement = $pdo->prepare('SELECT * FROM faqs WHERE is_active = 1 AND category = :category ORDER BY sort_order ASC, id DESC');
        $statement->execute(['category' => $category]);
        return $statement->fetchAll();
    }

    return $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY category ASC, sort_order ASC, id DESC')->fetchAll();
}

function faq_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = $pdo->query('SELECT DISTINCT category FROM faqs WHERE is_active = 1 ORDER BY category ASC')->fetchAll();
    return array_map(static fn(array $row): string => $row['category'], $rows);
}

function admin_faq_rows(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM faqs ORDER BY is_active DESC, category ASC, sort_order ASC, id DESC')->fetchAll() : [];
}

function admin_faq_row(mixed $id): ?array
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM faqs WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function save_faq(array $data): void
{
    if (array_key_exists('id', $data) && $data['id'] !== null) {
        $data['id'] = require_database_id($data['id']);
    }
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE faqs SET question = :question, answer = :answer, category = :category, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'faqs', 'FAQ diperbarui: ' . $data['question'], 'faq', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO faqs (question, answer, category, is_active, sort_order) VALUES (:question, :answer, :category, :is_active, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'faqs', 'FAQ dibuat: ' . $data['question'], 'faq', (int) $pdo->lastInsertId());
}

function delete_faq(mixed $id): bool
{
    $id = require_database_id($id);
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('Database tidak tersedia. Data tidak dihapus.');
    }
    if ($pdo) {
        $row = admin_faq_row($id);
        $statement = $pdo->prepare('DELETE FROM faqs WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            return false;
        }
        log_admin_activity('delete', 'faqs', 'FAQ dihapus: ' . ($row['question'] ?? ('ID ' . $id)), 'faq', $id);
        return true;
    }
    return false;
}

function faq_count(bool $activeOnly = false): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    if ($activeOnly) {
        return (int) $pdo->query('SELECT COUNT(*) FROM faqs WHERE is_active = 1')->fetchColumn();
    }

    return (int) $pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
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

function ppdb_entries(): array
{
    $pdo = db();
    return $pdo ? $pdo->query('SELECT * FROM ppdb_entries ORDER BY section ASC, sort_order ASC, id DESC')->fetchAll() : [];
}

function admin_ppdb_row(int $id): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM ppdb_entries WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function ppdb_sections_data(): array
{
    $entries = ppdb_entries();
    if ($entries) {
        $sections = [];
        foreach ($entries as $entry) {
            $section = strtolower(trim($entry['section']));
            if ($section === '') {
                $section = 'informasi';
            }

            $sections[$section][] = [
                'id' => (int) $entry['id'],
                'title' => $entry['title'],
                'desc' => $entry['content'],
            ];
        }

        if (empty($sections['kriteria'])) {
            $sections['kriteria'] = site_content()['ppdbCriteria'] ?? [];
        }

        return $sections;
    }

    $content = site_content();
    return [
        'informasi' => $content['ppdbInformation'] ?? [],
        'kriteria' => $content['ppdbCriteria'] ?? [],
        'persyaratan' => array_map(
            static fn(string $item): array => ['title' => 'Persyaratan', 'desc' => $item],
            $content['ppdbRequirements'] ?? []
        ),
        'catatan' => array_map(
            static fn(string $item): array => ['title' => 'Catatan Penting', 'desc' => $item],
            $content['ppdbNotes'] ?? []
        ),
    ];
}

function save_ppdb_entry(array $data): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE ppdb_entries SET section = :section, title = :title, content = :content, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'ppdb_content', 'Konten PPDB diperbarui: ' . $data['title'], 'ppdb_entry', (int) $data['id']);
        return;
    }

    $statement = $pdo->prepare('INSERT INTO ppdb_entries (section, title, content, sort_order) VALUES (:section, :title, :content, :sort_order)');
    unset($data['id']);
    $statement->execute($data);
    log_admin_activity('create', 'ppdb_content', 'Konten PPDB dibuat: ' . $data['title'], 'ppdb_entry', (int) $pdo->lastInsertId());
}

function delete_ppdb_entry(int $id): bool
{
    $pdo = db();
    if ($pdo) {
        $row = admin_ppdb_row($id);
        $statement = $pdo->prepare('DELETE FROM ppdb_entries WHERE id = :id');
        $statement->execute(['id' => $id]);
        $deleted = $statement->rowCount();
        if ($deleted > 0) {
            log_admin_activity('delete', 'ppdb_content', 'Konten PPDB dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'ppdb_entry', $id);
        }
        return $deleted > 0;
    }
    return false;
}

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
    log_admin_activity('update_status', 'contact_messages', 'Status pesan masuk diubah menjadi ' . $status . '.', 'contact_message', $id);
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
        log_admin_activity('delete', 'contact_messages', 'Pesan masuk dihapus.', 'contact_message', $id);
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



function public_search_results(string $query, int $limitPerType = 8): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $pdo = db();
    if (!$pdo) {
        return public_search_seed_results($query, $limitPerType);
    }

    ensure_news_publication_column();
    ensure_academic_year_columns();

    $like = '%' . $query . '%';
    $results = [];

    $statement = $pdo->prepare('SELECT slug, title, category, excerpt, published_at FROM news WHERE is_active = 1 AND (title LIKE :q OR category LIKE :q OR excerpt LIKE :q OR content LIKE :q) ORDER BY published_at DESC, id DESC LIMIT ' . max(1, (int) $limitPerType));
    $statement->execute(['q' => $like]);
    foreach ($statement->fetchAll() as $row) {
        $results[] = [
            'type' => 'Berita',
            'title' => $row['title'],
            'excerpt' => $row['excerpt'],
            'meta' => $row['category'] . ' - ' . date('d F Y', strtotime($row['published_at'])),
            'url' => base_url('berita/' . rawurlencode($row['slug'])),
        ];
    }

    $statement = $pdo->prepare('SELECT id, title, category, level, achiever_name, description, achievement_date FROM school_achievements WHERE is_active = 1 AND (title LIKE :q OR category LIKE :q OR level LIKE :q OR achiever_name LIKE :q OR description LIKE :q) ORDER BY achievement_date DESC, id DESC LIMIT ' . max(1, (int) $limitPerType));
    $statement->execute(['q' => $like]);
    foreach ($statement->fetchAll() as $row) {
        $results[] = [
            'type' => 'Prestasi',
            'title' => $row['title'],
            'excerpt' => $row['achiever_name'] . ' - ' . $row['description'],
            'meta' => $row['category'] . ' - ' . $row['level'] . ' - ' . date('d F Y', strtotime($row['achievement_date'])),
            'url' => base_url('prestasi/' . (int) $row['id']),
        ];
    }

    $searchables = [
        [
            'type' => 'Agenda',
            'sql' => 'SELECT title, category, location, description, starts_at FROM agenda_items WHERE is_active = 1 AND academic_year = :year AND (title LIKE :q OR category LIKE :q OR location LIKE :q OR description LIKE :q) ORDER BY starts_at ASC, id DESC LIMIT ' . max(1, (int) $limitPerType),
            'url' => base_url('agenda'),
            'mapper' => static fn(array $row): array => [
                'title' => $row['title'],
                'excerpt' => $row['description'],
                'meta' => $row['category'] . ' - ' . date('d F Y H:i', strtotime($row['starts_at'])) . ' - ' . $row['location'],
            ],
        ],
        [
            'type' => 'Dokumen',
            'sql' => 'SELECT title, category, description, created_at FROM school_documents WHERE is_active = 1 AND academic_year = :year AND (title LIKE :q OR category LIKE :q OR description LIKE :q OR original_filename LIKE :q) ORDER BY sort_order ASC, created_at DESC LIMIT ' . max(1, (int) $limitPerType),
            'url' => base_url('dokumen'),
            'mapper' => static fn(array $row): array => [
                'title' => $row['title'],
                'excerpt' => $row['description'],
                'meta' => $row['category'],
            ],
        ],
    ];

    foreach ($searchables as $item) {
        $statement = $pdo->prepare($item['sql']);
        $statement->execute(['q' => $like, 'year' => current_academic_year()]);
        foreach ($statement->fetchAll() as $row) {
            $mapped = $item['mapper']($row);
            $results[] = [
                'type' => $item['type'],
                'title' => $mapped['title'],
                'excerpt' => $mapped['excerpt'],
                'meta' => $mapped['meta'],
                'url' => $item['url'],
            ];
        }
    }

    $simpleTables = [
        ['type' => 'Fasilitas', 'table' => 'school_facilities', 'title' => 'name', 'meta' => 'category', 'excerpt' => 'description', 'url' => base_url('fasilitas')],
        ['type' => 'Ekskul', 'table' => 'extracurriculars', 'title' => 'name', 'meta' => 'category', 'excerpt' => 'description', 'url' => base_url('ekstrakurikuler')],
        ['type' => 'FAQ', 'table' => 'faqs', 'title' => 'question', 'meta' => 'category', 'excerpt' => 'answer', 'url' => base_url('faq')],
    ];

    foreach ($simpleTables as $item) {
        $sql = 'SELECT ' . repository_quote_identifier($item['title']) . ' AS title, ' . repository_quote_identifier($item['meta']) . ' AS meta, ' . repository_quote_identifier($item['excerpt']) . ' AS excerpt FROM ' . repository_quote_identifier($item['table']) . ' WHERE is_active = 1 AND (' . repository_quote_identifier($item['title']) . ' LIKE :q OR ' . repository_quote_identifier($item['meta']) . ' LIKE :q OR ' . repository_quote_identifier($item['excerpt']) . ' LIKE :q) ORDER BY sort_order ASC, id DESC LIMIT ' . max(1, (int) $limitPerType);
        $statement = $pdo->prepare($sql);
        $statement->execute(['q' => $like]);
        foreach ($statement->fetchAll() as $row) {
            $results[] = [
                'type' => $item['type'],
                'title' => $row['title'],
                'excerpt' => $row['excerpt'],
                'meta' => $row['meta'],
                'url' => $item['url'],
            ];
        }
    }

    return $results;
}

function public_search_seed_results(string $query, int $limitPerType = 8): array
{
    $query = strtolower($query);
    $results = [];

    foreach (news_items() as $item) {
        $haystack = strtolower(($item['title'] ?? '') . ' ' . ($item['category'] ?? '') . ' ' . ($item['desc'] ?? ''));
        if (str_contains($haystack, $query)) {
            $results[] = [
                'type' => 'Berita',
                'title' => $item['title'],
                'excerpt' => $item['desc'],
                'meta' => ($item['category'] ?? 'Berita') . ' - ' . ($item['date'] ?? ''),
                'url' => base_url('berita/' . rawurlencode($item['slug'])),
            ];
        }
    }

    return array_slice($results, 0, max(1, $limitPerType));
}

function admin_content_status_summary(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    return [
        ['permission' => 'news', 'label' => 'Berita', 'active' => news_count(true), 'total' => news_count(false), 'href' => 'admin/news.php'],
        ['permission' => 'achievements', 'label' => 'Prestasi', 'active' => achievement_count(true), 'total' => achievement_count(false), 'href' => 'admin/achievements.php'],
        ['permission' => 'agenda', 'label' => 'Agenda', 'active' => agenda_count(true), 'total' => agenda_count(false), 'href' => 'admin/agenda.php'],
        ['permission' => 'documents', 'label' => 'Dokumen', 'active' => document_count(true), 'total' => document_count(false), 'href' => 'admin/documents.php'],
        ['permission' => 'facilities', 'label' => 'Fasilitas', 'active' => facility_count(true), 'total' => facility_count(false), 'href' => 'admin/facilities.php'],
        ['permission' => 'extracurriculars', 'label' => 'Ekskul', 'active' => extracurricular_count(true), 'total' => extracurricular_count(false), 'href' => 'admin/extracurriculars.php'],
        ['permission' => 'staff', 'label' => 'Guru & Staf', 'active' => staff_count(true), 'total' => staff_count(false), 'href' => 'admin/staff.php'],
        ['permission' => 'faqs', 'label' => 'FAQ', 'active' => faq_count(true), 'total' => faq_count(false), 'href' => 'admin/faqs.php'],
    ];
}
