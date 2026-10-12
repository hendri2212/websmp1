<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/../includes/auth.php';

$checks = 0;
function role_assert(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

try {
    $root = dirname(__DIR__);
    role_assert(isset(admin_role_options()['ppdb']), 'Information operator remains a recognized role');
    role_assert((admin_permissions()['ppdb'] ?? []) === ['dashboard', 'ppdb_content', 'change_password'], 'PPDB role has only its three information permissions');
    role_assert(array_intersect(admin_permissions()['ppdb'], admin_sensitive_permissions()) === [], 'PPDB role has no sensitive administrative permissions');
    role_assert(in_array('ppdb_content', admin_permissions()['superadmin'], true) && !in_array('ppdb_content', admin_permissions()['editor'], true), 'Information ownership remains superadmin and ppdb, not editor');
    $endpoints = [];
    foreach (glob($root . '/admin/*.php') as $path) {
        $name = basename($path);
        if (in_array($name, ['index.php', 'login.php', 'logout.php'], true)) {
            continue;
        }
        $tokens = token_get_all(file_get_contents($path));
        if (in_array($name, ['ppdb_applications.php', 'ppdb_export.php', 'ppdb_download.php'], true)) {
            $source = file_get_contents($path);
            role_assert(str_contains($source, 'http_response_code(410);') && str_contains($source, 'exit('), 'Retired endpoint must terminate with 410: ' . $name);
            role_assert(!preg_match('/require|db\(|csrf_|save_ppdb|update_ppdb|delete_ppdb|readfile|fputcsv/', preg_replace('~/\*.*?\*/|//[^\n]*~s', '', $source)), 'Retired endpoint must have no active data operations: ' . $name);
            continue;
        }
        $significant = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $significant .= $token[1];
            } else {
                $significant .= $token;
            }
        }
        role_assert(preg_match("~^require__DIR__\\.'/?\\.\\./includes/bootstrap\\.php';require_admin_permission\\('([a-z_]+)'\\);~", $significant, $match) === 1, 'Missing first-line endpoint guard: ' . $name);
        $endpoints[$name] = $match[1];
        role_assert(isset(admin_permission_labels()[$match[1]]) || $match[1] === 'dashboard', 'Unknown endpoint permission: ' . $name);
        if (str_contains($significant, "['REQUEST_METHOD']") && str_contains($significant, "'POST'")) {
            role_assert(str_contains($significant, 'csrf_token_is_valid('), 'POST handler lacks CSRF validation: ' . $name);
        }
    }

    $menuSource = file_get_contents($root . '/templates/admin_header.php');
    foreach (admin_permissions() as $role => $permissions) {
        role_assert(!in_array('ppdb_applications', $permissions, true), 'No role may manage local applicants: ' . $role);
    }
    preg_match_all("~\\['permission' => '([a-z_]+)', 'href' => 'admin/([^']+)'~", $menuSource, $menuMatches, PREG_SET_ORDER);
    $ppdbMenus = array_values(array_filter($menuMatches, static fn(array $item): bool => str_starts_with($item[1], 'ppdb_') || str_starts_with($item[2], 'ppdb')));
    role_assert(count($ppdbMenus) === 1 && $ppdbMenus[0][1] === 'ppdb_content' && $ppdbMenus[0][2] === 'ppdb.php', 'Only the informative PPDB menu remains');
    $dashboardSource = file_get_contents($root . '/admin/dashboard.php');
    foreach (['ppdb_applications.php', 'ppdb_export.php', 'ppdb_download.php', 'ppdb_application_count('] as $legacy) {
        role_assert(!str_contains($menuSource . $dashboardSource, $legacy), 'Sidebar/dashboard excludes retired PPDB action: ' . $legacy);
    }
    foreach ($menuMatches as $item) {
        role_assert(($endpoints[$item[2]] ?? null) === $item[1], 'Menu and endpoint permission mismatch: ' . $item[2]);
    }
    role_assert(count($menuMatches) === count(admin_permissions()['superadmin']), 'Menu coverage differs from superadmin permissions');

    foreach (array_unique($endpoints) as $permission) {
        foreach (['superadmin', 'editor', 'ppdb', 'anonymous', 'unknown'] as $role) {
            $process = proc_open([PHP_BINARY, __DIR__ . '/auth_security_test.php', '--guard-probe', $role, $permission], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
            if (!is_resource($process)) {
                throw new RuntimeException('Cannot start guard probe');
            }
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $result = json_decode($output, true);
            role_assert($exit === 0 && $errors === '' && is_array($result), 'Guard probe failed: ' . $role . '/' . $permission . ' ' . $errors);
            $allowed = in_array($permission, admin_permissions()[$role] ?? [], true);
            $expectedStatus = in_array($role, ['anonymous', 'unknown'], true) ? 302 : ($allowed ? 200 : 403);
            role_assert($result['allowed'] === $allowed && $result['status'] === $expectedStatus, 'Incorrect guard decision: ' . $role . '/' . $permission);
            $expectedMenus = array_column(array_filter($menuMatches, static fn(array $item): bool => in_array($item[1], admin_permissions()[$role] ?? [], true)), 2);
            $actualMenus = $result['menus'];
            sort($expectedMenus);
            sort($actualMenus);
            role_assert($actualMenus === $expectedMenus, 'Rendered menu differs from role permissions: ' . $role . '/' . $permission);
            if ($expectedStatus === 403) {
                role_assert($result['denial_rendered'], 'Denied guard did not render 403 page');
            }
            if ($expectedStatus === 302) {
                role_assert($result['redirect'] === 'admin/login.php', 'Invalid session did not redirect to login');
            }
        }
    }

    echo 'Protected endpoints: ' . count($endpoints) . PHP_EOL;
    echo 'Permission/role guard probes: ' . (count(array_unique($endpoints)) * 5) . PHP_EOL;
    echo 'Menu entries checked: ' . count($menuMatches) . PHP_EOL;
    echo 'Passed assertions: ' . $checks . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
