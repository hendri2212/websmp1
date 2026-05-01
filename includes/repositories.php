<?php

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

    $content['navigation'] = normalized_navigation($content['navigation'] ?? [], seed_content()['navigation'] ?? []);
    $content['siteAcademic'] = normalized_site_academic($content['siteAcademic'] ?? [], seed_content()['siteAcademic'] ?? []);
    $content['siteHome'] = normalized_site_home($content['siteHome'] ?? [], seed_content()['siteHome'] ?? []);

    return $content;
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

    $defaultYear = seed_content()['siteAcademic']['currentYear'] ?? '2025/2026';
    $tables = ['agenda_items', 'school_documents', 'school_achievements', 'ppdb_applications'];
    foreach ($tables as $table) {
        $columns = $pdo->query('SHOW COLUMNS FROM ' . repository_quote_identifier($table))->fetchAll();
        $columnNames = array_map(static fn(array $column): string => $column['Field'], $columns);
        if (!in_array('academic_year', $columnNames, true)) {
            $pdo->exec('ALTER TABLE ' . repository_quote_identifier($table) . " ADD academic_year VARCHAR(20) NOT NULL DEFAULT '" . str_replace("'", "''", $defaultYear) . "'");
        }
        $statement = $pdo->prepare('UPDATE ' . repository_quote_identifier($table) . ' SET academic_year = :year WHERE academic_year = "" OR academic_year IS NULL');
        $statement->execute(['year' => $defaultYear]);
    }

    $done = true;
}

function normalized_site_home(array $settings, array $defaults): array
{
    $normalized = array_replace($defaults, $settings);
    foreach (['showAnnouncements', 'showQuickLinks', 'showLogoCard', 'showStats', 'showPrincipalCard', 'showTeacherCarousel', 'showProfileIntro', 'showFeaturedAreas', 'showAgenda'] as $key) {
        $normalized[$key] = array_key_exists($key, $normalized) ? (bool) $normalized[$key] : true;
    }

<<<<<<< HEAD
    foreach (['heroTitle', 'heroSubtitle', 'primaryButtonLabel', 'primaryButtonUrl', 'secondaryButtonLabel', 'secondaryButtonUrl', 'profileVideoUrl', 'profileVideoTitle', 'profileVideoDescription'] as $key) {
=======
    foreach (['heroTitle', 'heroSubtitle', 'primaryButtonLabel', 'primaryButtonUrl', 'secondaryButtonLabel', 'secondaryButtonUrl'] as $key) {
>>>>>>> 98147e3 (upload pertama)
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
        return;
    }

    $statement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($payload as $key => $value) {
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

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

function admin_user(int $id): ?array
{
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

function save_admin_user(array $data): array
{
    ensure_admin_role_columns();
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database belum terhubung.'];
    }

    $allowedRoles = ['superadmin', 'editor', 'ppdb'];
    if (!in_array($data['role'], $allowedRoles, true)) {
        return ['success' => false, 'message' => 'Role admin tidak valid.'];
    }

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

function delete_admin_user(int $id): array
{
    ensure_admin_role_columns();
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database belum terhubung.'];
    }

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

    $columns = $pdo->query('SHOW COLUMNS FROM news')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => $column['Field'], $columns);
    if (!in_array('is_active', $columnNames, true)) {
        $pdo->exec('ALTER TABLE news ADD is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER published_at');
    }

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

<<<<<<< HEAD
function news_count(bool $activeOnly = true): int
=======
function news_count(): int
>>>>>>> 98147e3 (upload pertama)
{
    $pdo = db();
    if (!$pdo) {
        return count(seed_content()['latestNews'] ?? []);
    }

    ensure_news_publication_column();
<<<<<<< HEAD
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
=======
    return (int) $pdo->query('SELECT COUNT(*) FROM news WHERE is_active = 1')->fetchColumn();
>>>>>>> 98147e3 (upload pertama)
}

function admin_news_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        ensure_news_publication_column();
        $statement = $pdo->prepare('UPDATE news SET slug = :slug, title = :title, category = :category, excerpt = :excerpt, content = :content, image_path = :image_path, published_at = :published_at, is_active = :is_active WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'news', 'Berita diperbarui: ' . $data['title'], 'news', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_news_publication_column();
    $statement = $pdo->prepare('INSERT INTO news (slug, title, category, excerpt, content, image_path, published_at, is_active) VALUES (:slug, :title, :category, :excerpt, :content, :image_path, :published_at, :is_active)');
    $statement->execute($data);
    log_admin_activity('create', 'news', 'Berita dibuat: ' . $data['title'], 'news', (int) $pdo->lastInsertId());
}

function delete_news(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_news_row($id);
        $statement = $pdo->prepare('DELETE FROM news WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'news', 'Berita dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'news', $id);
    }
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

function admin_gallery_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE gallery_items SET title = :title, description = :description, image_path = :image_path, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'gallery', 'Galeri diperbarui: ' . $data['title'], 'gallery_item', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO gallery_items (title, description, image_path, sort_order) VALUES (:title, :description, :image_path, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'gallery', 'Galeri dibuat: ' . $data['title'], 'gallery_item', (int) $pdo->lastInsertId());
}

function delete_gallery_item(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_gallery_row($id);
        $statement = $pdo->prepare('DELETE FROM gallery_items WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'gallery', 'Galeri dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'gallery_item', $id);
    }
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

function admin_announcement_row(int $id): ?array
{
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

function delete_announcement(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_announcement_row($id);
        $statement = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'announcements', 'Pengumuman dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'announcement', $id);
    }
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

function admin_staff_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE staff_members SET name = :name, position = :position, subject = :subject, bio = :bio, phone = :phone, email = :email, photo_path = :photo_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'staff', 'Data guru/staf diperbarui: ' . $data['name'], 'staff_member', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO staff_members (name, position, subject, bio, phone, email, photo_path, is_active, sort_order) VALUES (:name, :position, :subject, :bio, :phone, :email, :photo_path, :is_active, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'staff', 'Data guru/staf dibuat: ' . $data['name'], 'staff_member', (int) $pdo->lastInsertId());
}

function delete_staff_member(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_staff_row($id);
        $statement = $pdo->prepare('DELETE FROM staff_members WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'staff', 'Data guru/staf dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'staff_member', $id);
    }
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

<<<<<<< HEAD
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

=======
>>>>>>> 98147e3 (upload pertama)
function admin_agenda_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM agenda_items ORDER BY academic_year DESC, is_active DESC, starts_at ASC, sort_order ASC, id DESC')->fetchAll();
}

function admin_agenda_row(int $id): ?array
{
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

function delete_agenda_item(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_agenda_row($id);
        $statement = $pdo->prepare('DELETE FROM agenda_items WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'agenda', 'Agenda dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'agenda_item', $id);
    }
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

function admin_document_row(int $id): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM school_documents WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function public_document_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        ensure_academic_year_columns();
        $statement = $pdo->prepare('UPDATE school_documents SET title = :title, category = :category, description = :description, file_path = :file_path, original_filename = :original_filename, file_size = :file_size, is_active = :is_active, academic_year = :academic_year, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'documents', 'Dokumen diperbarui: ' . $data['title'], 'school_document', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $statement = $pdo->prepare('INSERT INTO school_documents (title, category, description, file_path, original_filename, file_size, is_active, academic_year, sort_order) VALUES (:title, :category, :description, :file_path, :original_filename, :file_size, :is_active, :academic_year, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'documents', 'Dokumen dibuat: ' . $data['title'], 'school_document', (int) $pdo->lastInsertId());
}

function delete_school_document(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_document_row($id);
        $statement = $pdo->prepare('DELETE FROM school_documents WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'documents', 'Dokumen dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'school_document', $id);
    }
}

function increment_document_download_count(int $id): void
{
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

<<<<<<< HEAD
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

function achievement_public_row(int $id): ?array
{
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

=======
>>>>>>> 98147e3 (upload pertama)
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

<<<<<<< HEAD
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

=======
>>>>>>> 98147e3 (upload pertama)
function admin_achievement_rows(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    ensure_academic_year_columns();
    return $pdo->query('SELECT * FROM school_achievements ORDER BY academic_year DESC, is_active DESC, achievement_date DESC, sort_order ASC, id DESC')->fetchAll();
}

function admin_achievement_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        ensure_academic_year_columns();
        $statement = $pdo->prepare('UPDATE school_achievements SET title = :title, level = :level, category = :category, achiever_name = :achiever_name, achievement_date = :achievement_date, description = :description, image_path = :image_path, is_active = :is_active, academic_year = :academic_year, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'achievements', 'Prestasi diperbarui: ' . $data['title'], 'achievement', (int) $data['id']);
        return;
    }

    unset($data['id']);
    ensure_academic_year_columns();
    $statement = $pdo->prepare('INSERT INTO school_achievements (title, level, category, achiever_name, achievement_date, description, image_path, is_active, academic_year, sort_order) VALUES (:title, :level, :category, :achiever_name, :achievement_date, :description, :image_path, :is_active, :academic_year, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'achievements', 'Prestasi dibuat: ' . $data['title'], 'achievement', (int) $pdo->lastInsertId());
}

function delete_achievement(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_achievement_row($id);
        $statement = $pdo->prepare('DELETE FROM school_achievements WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'achievements', 'Prestasi dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'achievement', $id);
    }
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

function admin_extracurricular_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE extracurriculars SET name = :name, category = :category, coach = :coach, schedule_text = :schedule_text, location = :location, description = :description, image_path = :image_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'extracurriculars', 'Ekstrakurikuler diperbarui: ' . $data['name'], 'extracurricular', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO extracurriculars (name, category, coach, schedule_text, location, description, image_path, is_active, sort_order) VALUES (:name, :category, :coach, :schedule_text, :location, :description, :image_path, :is_active, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'extracurriculars', 'Ekstrakurikuler dibuat: ' . $data['name'], 'extracurricular', (int) $pdo->lastInsertId());
}

function delete_extracurricular(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_extracurricular_row($id);
        $statement = $pdo->prepare('DELETE FROM extracurriculars WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'extracurriculars', 'Ekstrakurikuler dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'extracurricular', $id);
    }
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

function admin_facility_row(int $id): ?array
{
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
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if (!empty($data['id'])) {
        $statement = $pdo->prepare('UPDATE school_facilities SET name = :name, category = :category, location = :location, capacity = :capacity, condition_status = :condition_status, description = :description, image_path = :image_path, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute($data);
        log_admin_activity('update', 'facilities', 'Fasilitas diperbarui: ' . $data['name'], 'facility', (int) $data['id']);
        return;
    }

    unset($data['id']);
    $statement = $pdo->prepare('INSERT INTO school_facilities (name, category, location, capacity, condition_status, description, image_path, is_active, sort_order) VALUES (:name, :category, :location, :capacity, :condition_status, :description, :image_path, :is_active, :sort_order)');
    $statement->execute($data);
    log_admin_activity('create', 'facilities', 'Fasilitas dibuat: ' . $data['name'], 'facility', (int) $pdo->lastInsertId());
}

function delete_facility(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_facility_row($id);
        $statement = $pdo->prepare('DELETE FROM school_facilities WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'facilities', 'Fasilitas dihapus: ' . ($row['name'] ?? ('ID ' . $id)), 'facility', $id);
    }
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

function admin_faq_row(int $id): ?array
{
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

function delete_faq(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_faq_row($id);
        $statement = $pdo->prepare('DELETE FROM faqs WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'faqs', 'FAQ dihapus: ' . ($row['question'] ?? ('ID ' . $id)), 'faq', $id);
    }
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
    $statement->execute($data);
    log_admin_activity('create', 'ppdb_content', 'Konten PPDB dibuat: ' . $data['title'], 'ppdb_entry', (int) $pdo->lastInsertId());
}

function delete_ppdb_entry(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_ppdb_row($id);
        $statement = $pdo->prepare('DELETE FROM ppdb_entries WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'ppdb_content', 'Konten PPDB dihapus: ' . ($row['title'] ?? ('ID ' . $id)), 'ppdb_entry', $id);
    }
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

function update_contact_message_status(int $id, string $status): void
{
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

function delete_contact_message(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $statement = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'contact_messages', 'Pesan masuk dihapus.', 'contact_message', $id);
    }
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

function next_ppdb_registration_number(): string
{
    $pdo = db();
    $prefix = 'PPDB-' . date('Ymd') . '-';
    if (!$pdo) {
        return $prefix . strtoupper(bin2hex(random_bytes(3)));
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM ppdb_applications WHERE registration_number LIKE :prefix');
    $statement->execute(['prefix' => $prefix . '%']);
    $next = (int) $statement->fetchColumn() + 1;

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function ensure_ppdb_consent_columns(): void
{
    static $done = false;
    $pdo = db();
    if ($done || !$pdo) {
        return;
    }

    $columns = $pdo->query('SHOW COLUMNS FROM ppdb_applications')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => $column['Field'], $columns);

    if (!in_array('consent_accepted_at', $columnNames, true)) {
        $pdo->exec('ALTER TABLE ppdb_applications ADD consent_accepted_at DATETIME NULL AFTER photo_path');
    }
    if (!in_array('privacy_version', $columnNames, true)) {
        $pdo->exec('ALTER TABLE ppdb_applications ADD privacy_version VARCHAR(40) NULL AFTER consent_accepted_at');
    }
    if (!in_array('registration_path', $columnNames, true)) {
        $pdo->exec('ALTER TABLE ppdb_applications ADD registration_path VARCHAR(40) NOT NULL DEFAULT "" AFTER previous_school');
    }

    $done = true;
}

function save_ppdb_application(array $data): string
{
    $pdo = db();
    if (!$pdo) {
        return '';
    }

    ensure_ppdb_consent_columns();
    ensure_academic_year_columns();
    $registrationNumber = next_ppdb_registration_number();
    $statement = $pdo->prepare('INSERT INTO ppdb_applications (
        registration_number, student_name, birth_place, birth_date, gender, nisn, previous_school, registration_path,
        parent_name, parent_phone, parent_email, address, family_card_path, birth_certificate_path,
        report_card_path, photo_path, consent_accepted_at, privacy_version, academic_year
    ) VALUES (
        :registration_number, :student_name, :birth_place, :birth_date, :gender, :nisn, :previous_school, :registration_path,
        :parent_name, :parent_phone, :parent_email, :address, :family_card_path, :birth_certificate_path,
        :report_card_path, :photo_path, :consent_accepted_at, :privacy_version, :academic_year
    )');
    $statement->execute([
        'registration_number' => $registrationNumber,
        'student_name' => $data['student_name'],
        'birth_place' => $data['birth_place'],
        'birth_date' => $data['birth_date'],
        'gender' => $data['gender'],
        'nisn' => $data['nisn'],
        'previous_school' => $data['previous_school'],
        'registration_path' => $data['registration_path'] ?? '',
        'parent_name' => $data['parent_name'],
        'parent_phone' => $data['parent_phone'],
        'parent_email' => $data['parent_email'],
        'address' => $data['address'],
        'family_card_path' => $data['family_card_path'],
        'birth_certificate_path' => $data['birth_certificate_path'],
        'report_card_path' => $data['report_card_path'],
        'photo_path' => $data['photo_path'],
        'consent_accepted_at' => $data['consent_accepted_at'],
        'privacy_version' => $data['privacy_version'],
        'academic_year' => $data['academic_year'] ?? current_academic_year(),
    ]);

    return $registrationNumber;
}

function ppdb_application_filters(array $filters): array
{
    ensure_ppdb_consent_columns();
    ensure_academic_year_columns();
    $where = [];
    $params = [];

    $query = trim($filters['q'] ?? '');
    if ($query !== '') {
        $where[] = '(registration_number LIKE :query OR student_name LIKE :query OR nisn LIKE :query OR previous_school LIKE :query OR registration_path LIKE :query OR parent_name LIKE :query OR parent_phone LIKE :query OR parent_email LIKE :query)';
        $params['query'] = '%' . $query . '%';
    }

    $status = trim($filters['status'] ?? '');
    $allowedStatuses = ['baru', 'diverifikasi', 'diterima', 'ditolak'];
    if ($status !== '' && in_array($status, $allowedStatuses, true)) {
        $where[] = 'status = :status';
        $params['status'] = $status;
    }

    $year = trim($filters['academic_year'] ?? current_academic_year());
    if ($year !== '') {
        $where[] = 'academic_year = :academic_year';
        $params['academic_year'] = $year;
    }

    return [
        'where' => $where ? ' WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
    ];
}

function admin_ppdb_applications(array $filters = []): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $filterSql = ppdb_application_filters($filters);
    $statement = $pdo->prepare('SELECT * FROM ppdb_applications' . $filterSql['where'] . ' ORDER BY created_at DESC, id DESC');
    $statement->execute($filterSql['params']);
    return $statement->fetchAll();
}

function admin_ppdb_application_filtered_count(array $filters = []): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    $filterSql = ppdb_application_filters($filters);
    $statement = $pdo->prepare('SELECT COUNT(*) FROM ppdb_applications' . $filterSql['where']);
    $statement->execute($filterSql['params']);
    return (int) $statement->fetchColumn();
}

function admin_ppdb_application(int $id): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM ppdb_applications WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ?: null;
}

function ppdb_application_by_registration(string $registrationNumber, string $parentEmail): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM ppdb_applications WHERE registration_number = :registration_number AND parent_email = :parent_email LIMIT 1');
    $statement->execute([
        'registration_number' => $registrationNumber,
        'parent_email' => $parentEmail,
    ]);
    $row = $statement->fetch();
    return $row ?: null;
}

function update_ppdb_application_status(int $id, string $status, string $notes): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $allowed = ['baru', 'diverifikasi', 'diterima', 'ditolak'];
    if (!in_array($status, $allowed, true)) {
        return;
    }

    $statement = $pdo->prepare('UPDATE ppdb_applications SET status = :status, admin_notes = :admin_notes WHERE id = :id');
    $statement->execute([
        'id' => $id,
        'status' => $status,
        'admin_notes' => $notes,
    ]);
    $row = admin_ppdb_application($id);
    log_admin_activity('update_status', 'ppdb_applications', 'Status pendaftar PPDB diubah menjadi ' . $status . '.', 'ppdb_application', $id, [
        'registration_number' => $row['registration_number'] ?? null,
    ]);
}

function delete_ppdb_application(int $id): void
{
    $pdo = db();
    if ($pdo) {
        $row = admin_ppdb_application($id);
        $statement = $pdo->prepare('DELETE FROM ppdb_applications WHERE id = :id');
        $statement->execute(['id' => $id]);
        log_admin_activity('delete', 'ppdb_applications', 'Pendaftar PPDB dihapus: ' . ($row['registration_number'] ?? ('ID ' . $id)), 'ppdb_application', $id);
    }
}

function ppdb_application_count(?string $status = null): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    ensure_academic_year_columns();
    if ($status === null) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM ppdb_applications WHERE academic_year = :academic_year');
        $statement->execute(['academic_year' => current_academic_year()]);
        return (int) $statement->fetchColumn();
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM ppdb_applications WHERE status = :status AND academic_year = :academic_year');
    $statement->execute(['status' => $status, 'academic_year' => current_academic_year()]);
    return (int) $statement->fetchColumn();
}
<<<<<<< HEAD

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
            'url' => base_url('berita/' . $row['slug']),
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
                'url' => base_url('berita/' . $item['slug']),
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
=======
>>>>>>> 98147e3 (upload pertama)
