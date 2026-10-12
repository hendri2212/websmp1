<?php

declare(strict_types=1);

function stage4_ppdb_database_fingerprint(PDO $pdo): string
{
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    sort($tables);
    $state = [];
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $rows = array_map(static fn(array $row): string => json_encode($row, JSON_THROW_ON_ERROR),
            $pdo->query('SELECT * FROM ' . $quoted)->fetchAll(PDO::FETCH_ASSOC));
        sort($rows);
        $schema = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM)[1];
        $state[$table] = [$schema, $rows];
    }
    return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
}

function stage4_ppdb_legacy_security_checks(PDO $pdo, array $run, array $clients, array $anonymous, array $oldTokens): void
{
    $endpoints = [
        '/ppdb'=>[200,405], '/pages/ppdb.php'=>[404,405],
        '/ppdb/bukti'=>[410,410], '/pages/ppdb_receipt.php'=>[410,410],
        '/admin/ppdb_applications.php'=>[410,410], '/admin/ppdb_export.php'=>[410,410],
        '/admin/ppdb_download.php'=>[410,410],
    ];
    $baseline = stage4_ppdb_database_fingerprint($pdo);
    $uploads = content_state($pdo, 'ppdb_applications', $run);
    $requests = 0;
    $deniedPosts = 0;
    $unchanged = 0;
    $observations = [];
    foreach (['anonymous'=>$anonymous] + $clients as $role=>$client) {
        $current = content_token($client, $role === 'anonymous' ? '/admin/login.php' : '/admin/change_password.php');
        $old = $oldTokens[$role] ?? $oldTokens['superadmin'];
        content_check($old !== $current, $role . ': replay token is a real expired pre-login session token, not current token');
        foreach ($endpoints as $path=>$statuses) {
            foreach (['1','2147483647','123abc','../123',"123\0",['1']] as $id) {
                $reply = content_http($client, $path . '?' . http_build_query([
                    'id'=>$id, 'application_id'=>$id, 'registration_number'=>'TEST_TAHAP3_REPLAY',
                    'action'=>'export', 'file'=>'../../storage/uploads/ppdb/private.pdf', 'status'=>'diterima',
                ]));
                $requests++;
                $observations[] = ['role'=>$role,'method'=>'GET','path'=>$path,'status'=>$reply['status']];
                content_check($reply['status'] === $statuses[0] && !str_contains($reply['body'], '%PDF')
                    && !str_contains($reply['headers']['content-disposition'] ?? '', 'attachment'), $role . ': GET legacy ID/download/export cannot reopen ' . $path);
                $same = stage4_ppdb_database_fingerprint($pdo) === $baseline && content_state($pdo, 'ppdb_applications', $run) === $uploads;
                $unchanged += (int) $same;
                content_check($same, $role . ': GET leaves every table/schema/upload unchanged: ' . $path);
            }
            foreach (['missing'=>null, 'old'=>$old, 'invalid'=>str_repeat('0',64), 'current'=>$current] as $tokenCase=>$token) {
                foreach (['register','upload','status','delete','export'] as $action) {
                    $payload = [
                        'action'=>$action, 'id'=>'1', 'application_id'=>'1', 'delete_id'=>'1',
                        'status'=>'diterima', 'admin_notes'=>'TEST_TAHAP3_BLOCKED',
                        'role'=>'superadmin', 'registration_number'=>'TEST_TAHAP3_REPLAY',
                        'file'=>'../../storage/uploads/ppdb/private.pdf',
                    ];
                    if ($token !== null) {
                        $payload['csrf_token'] = $token;
                    }
                    foreach (['family_card_file','birth_certificate_file','report_card_file','photo_file'] as $field) {
                        $payload[$field] = new CURLFile($run['snapshot'] . '/fixture.pdf', 'application/pdf', 'TEST_TAHAP3_BLOCKED.pdf');
                    }
                    $reply = content_http($client, $path, $payload);
                    $requests++;
                    $observations[] = ['role'=>$role,'method'=>'POST','path'=>$path,'action'=>$action,
                        'token_case'=>$tokenCase,'status'=>$reply['status']];
                    $blocked = $reply['status'] === $statuses[1]
                        && ($statuses[1] !== 405 || ($reply['headers']['allow'] ?? '') === 'GET, HEAD');
                    $deniedPosts += (int) $blocked;
                    content_check($blocked && !str_contains($reply['body'], '%PDF')
                        && !str_contains($reply['headers']['content-disposition'] ?? '', 'attachment'),
                        $role . ': ' . $action . ' POST with ' . $tokenCase . ' CSRF denied: ' . $path);
                    $same = stage4_ppdb_database_fingerprint($pdo) === $baseline && content_state($pdo, 'ppdb_applications', $run) === $uploads;
                    $unchanged += (int) $same;
                    content_check($same, $role . ': POST leaves every table/schema/upload unchanged: ' . $path . '/' . $action . '/' . $tokenCase);
                }
            }
        }
    }
    file_put_contents($run['snapshot'] . '/ppdb-legacy-security-result.json', json_encode([
        'requests'=>$requests, 'blocked_posts'=>$deniedPosts, 'unchanged_after_requests'=>$unchanged,
        'database_fingerprint_unchanged'=>stage4_ppdb_database_fingerprint($pdo) === $baseline,
        'applicant_rows'=>(int) $pdo->query('SELECT COUNT(*) FROM ppdb_applications')->fetchColumn(),
        'schema_and_all_tables_compared'=>true, 'dummy_applicants_created'=>0,
        'stale_token_origin'=>'Actual pre-login token invalidated by successful synthetic admin login',
        'observations'=>$observations,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
