<?php

declare(strict_types=1);

function role_http_denied(PDO $pdo, array $base, array $client, string $role, string $path, string $table, array $payload, string $label, bool $pdf = false): void
{
    $before = content_state($pdo, $table, $base);
    $tokenPath = $role === 'anonymous' ? '/admin/login.php' : '/admin/change_password.php';
    $payload['csrf_token'] = content_token($client, $tokenPath);
    $payload['admin_role'] = 'superadmin';
    $payload['role'] = 'superadmin';
    $payload['admin_id'] = $base['super_id'];
    $reply = content_http($client, $path, $payload, $pdf);
    $denied = $role === 'anonymous'
        ? $reply['status'] === 302 && ($reply['headers']['location'] ?? '') === $base['url'] . '/admin/login.php'
        : $reply['status'] === 403;
    content_check($denied && content_state($pdo, $table, $base) === $before,
        $role . ' direct POST ' . $label . ' denied with valid CSRF and forged role; no mutation');
}

function role_account_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username');
    $stmt->execute(['username'=>$username]);
    return $stmt->fetch() ?: null;
}

function stage5_backup_probe_checks(array $run, ?string $archive = null): void
{
    $probe = $run['snapshot'] . '/admin/backup-security-probe.php';
    copy(__DIR__ . '/stage5_backup_probe.php', $probe);
    $command = [PHP_BINARY, '-d', 'extension=C:/xampp/php/ext/php_zip.dll', $probe, dirname(__DIR__) . '/stage3_database_preflight.php'];
    if ($archive !== null) { $command[] = $archive; }
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['file',$run['snapshot'] . '/backup-security-probe.err.log','a']], $pipes);
    content_require(is_resource($process), 'Backup probe could not start');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    content_require(proc_close($process) === 0, 'Backup probe failed; inspect private log');
    foreach (json_decode($output, true, 512, JSON_THROW_ON_ERROR) as $check) { content_check($check['passed'], $check['label']); }
}

function stage3_crud_role_checks(PDO $pdo, array $run, array $clients, array $anonymous, array $cases, array &$ownedAccounts, array &$ownedSettings, string $password): void
{
    $run['super_id'] = $ownedAccounts['superadmin']['id'];
    $allClients = $clients + ['anonymous'=>$anonymous];
    // Explicit expected matrix, independent of the application's permission helper.
    $editorAllowed = array_merge(array_keys($cases), ['contact_messages','dashboard','change_password']);
    $ppdbAllowed = ['dashboard','change_password'];
    $endpoints = array_merge(array_keys($cases), ['contact_messages','settings','admins','db_backup','activity_logs','production_check','dashboard','change_password']);
    foreach ($allClients as $role => $client) {
        foreach ($endpoints as $endpoint) {
            $allowed = $role === 'superadmin' || ($role === 'editor' && in_array($endpoint, $editorAllowed, true))
                || ($role === 'ppdb' && in_array($endpoint, $ppdbAllowed, true));
            $reply = content_http($client, '/admin/' . $endpoint . '.php?role=superadmin&admin_role=superadmin&admin_id=' . $run['super_id']);
            $expected = $allowed ? 200 : ($role === 'anonymous' ? 302 : 403);
            $correct = $reply['status'] === $expected;
            if ($role === 'anonymous') {
                $correct = $correct && ($reply['headers']['location'] ?? '') === $run['url'] . '/admin/login.php';
            }
            content_check($correct, $role . ' direct GET ' . $endpoint . ' matches expected server permission');
        }
    }

    foreach ($cases as $module => $case) {
        $path = '/admin/' . $module . '.php';
        $label = 'TEST_TAHAP3_ROLE_' . $case['label'] . '_' . $run['tag'];
        $guardPayload = content_payload($case, $label . '_GUARD');
        $reply = content_submit($clients['superadmin'], $path, $guardPayload, $module === 'documents');
        $guard = content_row($pdo, $case, $label . '_GUARD');
        content_require($reply['status'] === 200 && $guard !== null, 'Role test guard could not be created: ' . $module);
        foreach (['ppdb','anonymous'] as $role) {
            $client = $allClients[$role];
            $reply = content_http($client, $path . '?edit=' . $guard['id']);
            content_check($reply['status'] === ($role === 'anonymous' ? 302 : 403) && !str_contains($reply['body'], $label . '_GUARD'), $role . ' direct GET edit ' . $module . ' cannot read record');
            role_http_denied($pdo, $run, $client, $role, $path, $case['table'], content_payload($case, $label . '_UNAUTHORIZED'), $module . ' CREATE', $module === 'documents');
            $update = content_payload($case, $label . '_UNAUTHORIZED_EDIT');
            $update['id'] = (string) $guard['id'];
            role_http_denied($pdo, $run, $client, $role, $path, $case['table'], $update, $module . ' UPDATE');
            role_http_denied($pdo, $run, $client, $role, $path, $case['table'], ['delete_id'=>(string) $guard['id']], $module . ' DELETE');
            $update['id'] .= 'abc';
            role_http_denied($pdo, $run, $client, $role, $path, $case['table'], $update, $module . ' malformed UPDATE');
            role_http_denied($pdo, $run, $client, $role, $path, $case['table'], ['delete_id'=>$guard['id'] . 'abc'], $module . ' malformed DELETE');
        }
        foreach (['superadmin','editor'] as $role) {
            $client = $clients[$role];
            $payload = content_payload($case, $label . '_' . strtoupper($role));
            $reply = content_submit($client, $path, $payload, $module === 'documents');
            $row = content_row($pdo, $case, $payload[$case['field']]);
            content_check($reply['status'] === 200 && $row !== null, $role . ' ' . $module . ' CREATE authorized and persisted');
            content_require($row !== null, 'Authorized CREATE failed: ' . $role . ' ' . $module);
            $read = content_http($client, $path . '?edit=' . $row['id']);
            content_check($read['status'] === 200 && str_contains($read['body'], $payload[$case['field']]), $role . ' ' . $module . ' READ direct edit renders own fixture');
            $payload['id'] = (string) $row['id'];
            $payload[$case['field']] .= '_EDIT';
            if ($module === 'news') {
                $payload['slug'] .= '-edit';
            }
            $reply = content_submit($client, $path, $payload);
            $edited = content_row($pdo, $case, $payload[$case['field']]);
            content_check($reply['status'] === 200 && $edited !== null && (int) $edited['id'] === (int) $row['id']
                && content_row($pdo, $case, $label . '_GUARD') === $guard, $role . ' ' . $module . ' UPDATE authorized; guard record unchanged');
            $read = content_http($client, $path . '?edit=' . $row['id']);
            content_check($read['status'] === 200 && str_contains($read['body'], $payload[$case['field']]), $role . ' ' . $module . ' updated record readable');
            $reply = content_submit($client, $path, ['delete_id'=>(string) $row['id']]);
            content_check($reply['status'] === 200 && content_row($pdo, $case, $payload[$case['field']]) === null
                && content_row($pdo, $case, $label . '_GUARD') === $guard, $role . ' ' . $module . ' DELETE authorized; guard record unchanged');
        }
    }

    $messageCase = ['table'=>'contact_messages','field'=>'subject'];
    foreach (['superadmin','editor'] as $role) {
        $label = 'TEST_TAHAP3_ROLE_PESAN_' . $run['tag'] . '_' . $role;
        $reply = content_submit($anonymous, '/kontak', ['name'=>'Synthetic visitor','email'=>'synthetic@example.invalid','phone'=>'','subject'=>$label,'message'=>'Synthetic permission test']);
        $row = content_row($pdo, $messageCase, $label);
        content_require($reply['status'] === 200 && $row !== null, 'Incoming message fixture unavailable.');
        $client = $clients[$role];
        $read = content_http($client, '/admin/contact_messages.php');
        content_check($read['status'] === 200 && str_contains($read['body'], $label), $role . ' messages READ authorized');
        foreach (['ppdb','anonymous'] as $blockedRole) {
            role_http_denied($pdo, $run, $allClients[$blockedRole], $blockedRole, '/admin/contact_messages.php', 'contact_messages', ['message_id'=>(string) $row['id'],'status'=>'selesai'], 'messages UPDATE');
            role_http_denied($pdo, $run, $allClients[$blockedRole], $blockedRole, '/admin/contact_messages.php', 'contact_messages', ['delete_id'=>(string) $row['id']], 'messages DELETE');
        }
        $reply = content_submit($client, '/admin/contact_messages.php', ['message_id'=>(string) $row['id'],'status'=>'diproses']);
        content_check($reply['status'] === 200 && content_row($pdo, $messageCase, $label)['status'] === 'diproses', $role . ' messages UPDATE status authorized');
        $reply = content_submit($client, '/admin/contact_messages.php', ['delete_id'=>(string) $row['id']]);
        content_check($reply['status'] === 200 && content_row($pdo, $messageCase, $label) === null, $role . ' messages DELETE authorized');
    }

    $settings = ['name'=>'TEST_TAHAP3_ROLE_PROFIL_' . $run['tag'], 'history_text'=>'Synthetic history', 'home_show_announcements'=>'1'];
    foreach (['editor','ppdb','anonymous'] as $role) {
        role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/settings.php', 'site_settings', $settings, 'settings initial SAVE');
    }
    $reply = content_submit($clients['superadmin'], '/admin/settings.php', $settings);
    $ownedSettings = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
    $profile = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'schoolProfile'")->fetchColumn();
    content_check($reply['status'] === 200 && json_decode((string) $profile, true)['name'] === $settings['name'], 'superadmin settings CREATE/upsert authorized');
    $settings['name'] .= '_EDIT';
    foreach (['editor','ppdb','anonymous'] as $role) {
        role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/settings.php', 'site_settings', $settings, 'settings UPDATE');
    }
    $reply = content_submit($clients['superadmin'], '/admin/settings.php', $settings);
    $profile = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'schoolProfile'")->fetchColumn();
    content_check($reply['status'] === 200 && json_decode((string) $profile, true)['name'] === $settings['name'], 'superadmin settings UPDATE authorized');

    $accountPayload = ['id'=>'','username'=>$run['tag'] . '_account_guard','full_name'=>'Synthetic account guard', 'role'=>'editor','is_active'=>'1','password'=>$password];
    $reply = content_submit($clients['superadmin'], '/admin/admins.php', $accountPayload);
    $guard = role_account_by_username($pdo, $accountPayload['username']);
    content_require($reply['status'] === 200 && $guard !== null, 'Account guard unavailable.');
    $ownedAccounts['account_guard'] = ['id'=>(int) $guard['id'],'username'=>$guard['username']];
    foreach (['editor','ppdb','anonymous'] as $role) {
        $create = array_replace($accountPayload, ['username'=>$run['tag'] . '_unauthorized_' . $role]);
        role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/admins.php', 'admins', $create, 'accounts CREATE');
        $update = array_replace($accountPayload, ['id'=>(string) $guard['id'],'role'=>'superadmin','full_name'=>'Unauthorized edit']);
        role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/admins.php', 'admins', $update, 'accounts UPDATE/promotion');
        role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/admins.php', 'admins', ['delete_id'=>(string) $guard['id']], 'accounts DELETE');
        if ($role !== 'anonymous') {
            $escalate = array_replace($update, ['id'=>(string) $ownedAccounts[$role]['id'],'username'=>$ownedAccounts[$role]['username']]);
            role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/admins.php', 'admins', $escalate, 'accounts self privilege escalation');
        }
    }
    $accountPayload['username'] = $run['tag'] . '_account_main';
    $reply = content_submit($clients['superadmin'], '/admin/admins.php', $accountPayload);
    $account = role_account_by_username($pdo, $accountPayload['username']);
    content_check($reply['status'] === 200 && $account !== null, 'superadmin accounts CREATE authorized');
    content_require($account !== null, 'Synthetic account CREATE failed.');
    $ownedAccounts['account_main'] = ['id'=>(int) $account['id'],'username'=>$account['username']];
    $read = content_http($clients['superadmin'], '/admin/admins.php?edit=' . $account['id']);
    content_check($read['status'] === 200 && str_contains($read['body'], $account['username']), 'superadmin accounts READ authorized');
    $accountPayload['id'] = (string) $account['id'];
    $accountPayload['full_name'] = 'Synthetic account edited';
    $accountPayload['role'] = 'superadmin';
    $accountPayload['password'] = '';
    $reply = content_submit($clients['superadmin'], '/admin/admins.php', $accountPayload);
    $account = role_account_by_username($pdo, $accountPayload['username']);
    content_check($reply['status'] === 200 && $account['role'] === 'superadmin' && $account['full_name'] === 'Synthetic account edited'
        && role_account_by_username($pdo, $guard['username']) === $guard, 'superadmin accounts UPDATE/promotion authorized; guard unchanged');
    unset($accountPayload['is_active']);
    $reply = content_submit($clients['superadmin'], '/admin/admins.php', $accountPayload);
    content_check($reply['status'] === 200 && (int) role_account_by_username($pdo, $accountPayload['username'])['is_active'] === 0, 'superadmin accounts status deactivation authorized on synthetic target');
    $reply = content_submit($clients['superadmin'], '/admin/admins.php', ['delete_id'=>(string) $account['id']]);
    content_check($reply['status'] === 200 && role_account_by_username($pdo, $accountPayload['username']) === null
        && role_account_by_username($pdo, $guard['username']) === $guard, 'superadmin accounts DELETE authorized; primary superadmin/guard unchanged');

    foreach (['sql','full'] as $type) {
        $beforeLogs = (int) $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE module = 'db_backup'")->fetchColumn();
        $get = content_http($clients['superadmin'], '/admin/db_backup.php?' . http_build_query(['backup_type'=>$type,'filename'=>'../../config/database.php']));
        content_check($get['status'] === 200 && str_contains($get['headers']['content-type'] ?? '', 'text/html')
            && !isset($get['headers']['content-disposition']), 'backup ' . $type . ' GET/query cannot trigger export');
        foreach ([null, 'invalid-old-token'] as $badToken) {
            $payload = ['backup_type'=>$type];
            if ($badToken !== null) { $payload['csrf_token'] = $badToken; }
            $denied = content_http($clients['superadmin'], '/admin/db_backup.php', $payload);
            content_check($denied['status'] === 200 && !isset($denied['headers']['content-disposition'])
                && str_contains($denied['body'], 'Sesi form sudah tidak valid.'), 'backup ' . $type . ' missing/invalid CSRF denies export');
        }
        content_check((int) $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE module = 'db_backup'")->fetchColumn() === $beforeLogs,
            'backup ' . $type . ' rejected requests create no export log');
        foreach (['editor','ppdb','anonymous'] as $role) {
            role_http_denied($pdo, $run, $allClients[$role], $role, '/admin/db_backup.php', 'admins', ['backup_type'=>$type], 'backup ' . $type);
        }
        $reply = content_submit($clients['superadmin'], '/admin/db_backup.php', ['backup_type'=>$type]);
        $correct = $type === 'sql'
            ? str_starts_with($reply['headers']['content-type'] ?? '', 'application/sql') && str_contains($reply['body'], 'website_sekolah_test_stage3')
            : ($reply['headers']['content-type'] ?? '') === 'application/zip' && str_starts_with($reply['body'], 'PK');
        content_check($reply['status'] === 200 && $correct, 'superadmin backup ' . $type . ' authorized; synthetic export only, never restored');
        $connection = json_decode(file_get_contents(dirname($run['snapshot']) . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
        if ($type === 'sql') {
            content_check(!str_contains($reply['body'], $connection['password']) && !str_contains($reply['body'], $connection['username'])
                && !str_contains($reply['body'], 'mysql:host='), 'SQL backup excludes database connection credentials/DSN');
        } else {
            $archive = $run['snapshot'] . '/backup-security-response.zip';
            file_put_contents($archive, $reply['body']);
            stage5_backup_probe_checks($run, $archive);
            unlink($archive);
            content_check((glob($run['snapshot'] . '/storage/backups/.temporary/*.zip') ?: []) === [], 'ZIP download removes private temporary archive');
            foreach (['/storage/backups/.temporary/nonexistent.zip','/config/database.php','/manifest.json'] as $privatePath) {
                $private = content_http($anonymous, $privatePath);
                content_check($private['status'] === 403, 'Anonymous cannot retrieve private backup/config fixture ' . $privatePath);
            }
        }
    }
    stage5_backup_probe_checks($run);
    foreach (['activity_logs','production_check','dashboard'] as $endpoint) {
        foreach ($allClients as $role => $client) {
            $allowed = $role === 'superadmin' || ($endpoint === 'dashboard' && $role !== 'anonymous');
            if (!$allowed) {
                role_http_denied($pdo, $run, $client, $role, '/admin/' . $endpoint . '.php', 'admins', ['action'=>'direct_probe'], $endpoint . ' read-only handler');
            } else {
                $before = content_state($pdo, 'admins', $run);
                $reply = content_submit($client, '/admin/' . $endpoint . '.php', ['action'=>'direct_probe']);
                content_check($reply['status'] === 200 && content_state($pdo, 'admins', $run) === $before, $role . ' direct POST ' . $endpoint . ' authorized read-only response');
            }
        }
    }
    foreach (['superadmin','editor'] as $role) {
        $beforeGuard = role_account_by_username($pdo, $guard['username']);
        $newPassword = bin2hex(random_bytes(24));
        $reply = content_submit($clients[$role], '/admin/change_password.php', ['current_password'=>$password,'new_password'=>$newPassword,
            'confirm_password'=>$newPassword,'admin_id'=>(string) $guard['id'],'username'=>$guard['username'],'role'=>'superadmin']);
        $own = role_account_by_username($pdo, $ownedAccounts[$role]['username']);
        content_check($reply['status'] === 200 && password_verify($newPassword, $own['password_hash'])
            && role_account_by_username($pdo, $guard['username']) === $beforeGuard, $role . ' change_password affects own account only despite forged target ID');
    }
    role_http_denied($pdo, $run, $anonymous, 'anonymous', '/admin/change_password.php', 'admins',
        ['current_password'=>$password,'new_password'=>$password,'confirm_password'=>$password], 'change_password');
    foreach (['editor','ppdb'] as $role) {
        content_check(role_account_by_username($pdo, $ownedAccounts[$role]['username'])['role'] === $role, $role . ' database role unchanged after all spoofed requests');
    }
}
