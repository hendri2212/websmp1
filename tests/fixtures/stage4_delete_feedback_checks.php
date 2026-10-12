<?php

function stage4_delete_feedback_checks(PDO $pdo, array $run, array $clients, array $anonymous, array $cases, array &$ownedAccounts, string $password): void
{
    foreach ($cases + ['contact_messages'=>['table'=>'contact_messages','field'=>'subject']] as $module=>$case) {
        $label = 'TEST_TAHAP3_DELETE_' . $run['tag'] . '_' . $module;
        $path = '/admin/' . $module . '.php';
        if ($module === 'contact_messages') {
            content_submit($anonymous, '/kontak', ['name'=>'TEST_TAHAP3_DELETE','email'=>'delete@example.invalid',
                'phone'=>'','subject'=>$label,'message'=>'Pesan sintetis aman untuk test delete.']);
        } else {
            content_submit($run, $path, content_payload($case, $label), $module === 'documents');
        }
        $row = content_row($pdo, $case, $label);
        content_require($row !== null, 'Synthetic delete fixture missing: ' . $module);
        $before = content_state($pdo, $case['table'], $run);
        $logCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE action = 'delete'")->fetchColumn();
        $reply = content_submit($run, $path, ['delete_id'=>'2147483647']);
        content_check($reply['status'] === 200 && str_contains($reply['body'], 'Data tidak ditemukan atau sudah dihapus.')
            && !str_contains($reply['body'], 'class="alert success"') && content_state($pdo, $case['table'], $run) === $before,
            $module . ' missing delete: error feedback without false success or mutation');
        content_check((int) $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE action = 'delete'")->fetchColumn() === $logCount,
            $module . ' missing delete: no misleading successful delete audit log');
        $reply = content_submit($run, $path, ['delete_id'=>$row['id']]);
        content_check($reply['status'] === 200 && str_contains($reply['body'], 'class="alert success"')
            && str_contains($reply['body'], 'berhasil dihapus.') && content_row($pdo, $case, $label) === null,
            $module . ' existing delete: confirmed deletion produces success feedback');
        $after = content_state($pdo, $case['table'], $run);
        $reply = content_submit($run, $path, ['delete_id'=>$row['id']]);
        content_check($reply['status'] === 200 && str_contains($reply['body'], 'Data tidak ditemukan atau sudah dihapus.')
            && !str_contains($reply['body'], 'class="alert success"') && content_state($pdo, $case['table'], $run) === $after,
            $module . ' repeated delete: already deleted record never succeeds');
    }

    $username = 'TEST_TAHAP3_DELETE_' . $run['tag'];
    $statement = $pdo->prepare('INSERT INTO admins (username,password_hash,full_name,role,is_active) VALUES (?,?,?,?,?)');
    $statement->execute([$username,password_hash($password,PASSWORD_DEFAULT),'TEST_TAHAP3_DELETE_ACCOUNT','editor',1]);
    $id = (int) $pdo->lastInsertId();
    $ownedAccounts[] = ['id'=>$id,'username'=>$username];
    foreach (['2147483647', (string) $id, (string) $id] as $index=>$deleteId) {
        $reply = content_submit($run, '/admin/admins.php', ['delete_id'=>$deleteId]);
        content_check($reply['status'] === 200 && ($index === 1
            ? str_contains($reply['body'], 'class="alert success"') && str_contains($reply['body'], 'Akun admin berhasil dihapus.')
            : !str_contains($reply['body'], 'class="alert success"') && str_contains($reply['body'], 'Akun admin tidak ditemukan')),
            'admins delete feedback: ' . ['missing','confirmed success','repeated missing'][$index]);
    }
    $super = array_values(array_filter($ownedAccounts, static fn($account)=>str_contains($account['username'], 'superadmin')));
    content_require(count($super) === 1, 'Cannot identify exact owned superadmin fixture.');
    $reply = content_submit($run, '/admin/admins.php', ['delete_id'=>$super[0]['id']]);
    content_check(str_contains($reply['body'], 'Akun yang sedang login tidak bisa dihapus.')
        && !str_contains($reply['body'], 'class="alert success"'), 'account delete: self-deletion protection preserved');

    $payload = ['delete_id'=>'2147483647','csrf_token'=>'invalid'];
    $reply = content_http($run, '/admin/news.php', $payload);
    content_check(str_contains($reply['body'], 'Sesi form sudah tidak valid') && !str_contains($reply['body'], 'Data tidak ditemukan'),
        'delete CSRF: rejected before repository feedback');
    $reply = content_http($clients['ppdb'], '/admin/news.php', ['delete_id'=>'2147483647',
        'csrf_token'=>content_token($clients['ppdb'], '/admin/change_password.php')]);
    content_check($reply['status'] === 403, 'delete authorization: PPDB role cannot invoke general module delete');
    $reply = content_http($anonymous, '/admin/news.php', ['delete_id'=>'2147483647','csrf_token'=>'invalid']);
    content_check($reply['status'] === 302, 'delete authentication: anonymous request denied');
}
