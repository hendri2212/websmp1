<?php

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

function active_admin_count(): int
{
    $pdo = db();
    return $pdo ? (int) $pdo->query('SELECT COUNT(*) FROM admins WHERE is_active = 1')->fetchColumn() : 0;
}
