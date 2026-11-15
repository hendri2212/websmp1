<?php

require_once APP_ROOT . '/app/core/schema_requirements.php';

function admin_is_logged_in(): bool
{
    return admin_session_is_active();
}

function admin_username(): ?string
{
    return $_SESSION['admin_username'] ?? null;
}

function admin_role(): string
{
    $role = $_SESSION['admin_role'] ?? '';
    return is_string($role) && isset(admin_role_options()[$role]) ? $role : '';
}

function admin_role_label(?string $role = null): string
{
    return admin_role_options()[$role ?? admin_role()] ?? 'Editor Konten';
}

function admin_role_options(): array
{
    return [
        'superadmin' => 'Superadmin',
        'editor' => 'Editor Konten',
        'ppdb' => 'Operator PPDB',
    ];
}

function admin_permissions(): array
{
    return [
        'superadmin' => [
            'dashboard', 'settings', 'admins', 'db_backup', 'production_check', 'facilities', 'agenda', 'documents', 'achievements',
            'extracurriculars', 'staff', 'news', 'announcements', 'gallery', 'faqs', 'ppdb_content',
            'contact_messages', 'change_password',
        ],
        'editor' => [
            'dashboard', 'facilities', 'agenda', 'documents', 'achievements', 'extracurriculars',
            'staff', 'news', 'announcements', 'gallery', 'faqs', 'contact_messages', 'change_password',
        ],
        'ppdb' => ['dashboard', 'ppdb_content', 'change_password'],
    ];
}

function admin_sensitive_permissions(): array
{
    return ['settings', 'admins', 'db_backup', 'production_check'];
}

function admin_permission_labels(): array
{
    return [
        'settings' => 'Profil & pengaturan website',
        'admins' => 'Akun admin/operator',
        'db_backup' => 'Backup database dan file',
        'production_check' => 'Cek production',
        'news' => 'Berita',
        'announcements' => 'Pengumuman',
        'gallery' => 'Galeri',
        'staff' => 'Guru & Staf',
        'facilities' => 'Fasilitas',
        'agenda' => 'Agenda',
        'documents' => 'Dokumen',
        'achievements' => 'Prestasi',
        'extracurriculars' => 'Ekstrakurikuler',
        'faqs' => 'FAQ',
        'ppdb_content' => 'Konten PPDB',
        'contact_messages' => 'Pesan masuk',
        'change_password' => 'Ganti password sendiri',
    ];
}

function admin_role_permission_label(string $role, string $permission): string
{
    if ($role === 'ppdb' && $permission === 'documents') {
        return 'Dokumen PPDB';
    }

    $labels = admin_permission_labels();
    return $labels[$permission] ?? $permission;
}

function admin_role_descriptions(): array
{
    return [
        'superadmin' => 'Akses penuh termasuk pengaturan, backup, akun admin, dan cek production.',
        'editor' => 'Mengelola konten publik dan pesan masuk tanpa akses ke fitur sensitif.',
        'ppdb' => 'Mengelola informasi PPDB tanpa pendaftaran lokal atau akses ke konten umum lain.',
    ];
}

function admin_can(string $permission): bool
{
    if (!admin_is_logged_in()) {
        return false;
    }
    $role = admin_role();
    return in_array($permission, admin_permissions()[$role] ?? [], true);
}

function admin_session_is_active(): bool
{
    $id = $_SESSION['admin_id'] ?? null;
    if (($_SESSION['admin_logged_in'] ?? false) !== true || !is_int($id) || $id <= 0) {
        admin_clear_authentication();
        return false;
    }

    try {
        $pdo = db();
        if (!$pdo) {
            admin_clear_authentication();
            return false;
        }

        $statement = $pdo->prepare('SELECT username, role, is_active FROM admins WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $admin = $statement->fetch();
        if (!$admin || (int) ($admin['is_active'] ?? 0) !== 1 || !isset(admin_role_options()[$admin['role'] ?? ''])) {
            admin_clear_authentication();
            return false;
        }
    } catch (Throwable $exception) {
        admin_clear_authentication();
        return false;
    }

    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];
    return true;
}

function admin_clear_authentication(): void
{
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role']);
}

function admin_login(mixed $username, mixed $password): bool
{
    admin_clear_authentication();
    if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
        return false;
    }

    try {
        $pdo = db();
        if (!$pdo) {
            return false;
        }
        $statement = $pdo->prepare('SELECT * FROM admins WHERE username = :username AND is_active = 1 LIMIT 1');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();

        if ($admin && (int) ($admin['is_active'] ?? 0) === 1
            && isset(admin_role_options()[$admin['role'] ?? ''])
            && password_verify($password, $admin['password_hash'])) {
            if (!session_regenerate_id(true)) {
                return false;
            }
            unset($_SESSION['csrf_token']);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];

            return true;
        }
    } catch (Throwable $exception) {
        admin_clear_authentication();
        return false;
    }

    return false;
}

function admin_logout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
}

function update_admin_password(string $username, string $currentPassword, string $newPassword): array
{
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database belum terhubung. Fitur ganti password membutuhkan MySQL aktif.'];
    }

    $statement = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $admin = $statement->fetch();

    if (!$admin) {
        return ['success' => false, 'message' => 'Akun admin tidak ditemukan di database.'];
    }

    $currentMatches = password_verify($currentPassword, $admin['password_hash']);

    if (!$currentMatches) {
        return ['success' => false, 'message' => 'Password saat ini tidak sesuai.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'Password baru minimal 8 karakter.'];
    }

    $update = $pdo->prepare('UPDATE admins SET password_hash = :password_hash WHERE username = :username');
    $update->execute([
        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        'username' => $username,
    ]);

    return ['success' => true, 'message' => 'Password admin berhasil diperbarui.'];
}

function require_admin(): void
{
    if (!admin_is_logged_in()) {
        redirect_to('admin/login.php');
    }
}

function require_admin_permission(string $permission): void
{
    require_admin();

    if (!admin_can($permission)) {
        http_response_code(403);
        $pageTitle = 'Akses Ditolak';
        render_view('admin/forbidden.php', ['pageTitle' => $pageTitle], 'admin');
        exit;
    }
}

function ensure_admin_role_columns(): void
{
    static $done = false;
    $pdo = db();
    if ($done || !$pdo) {
        return;
    }

    require_schema_columns($pdo, 'admins', ['full_name', 'role', 'is_active']);

    $done = true;
}
