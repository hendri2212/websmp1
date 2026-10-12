<?php

declare(strict_types=1);

function stage4_input_validation_checks(PDO $pdo, array $run, array $anonymous, array $cases, array $accounts, array &$ownedSettings): void
{
    foreach ($cases as $module => $case) {
        $path = '/admin/' . $module . '.php';
        $label = 'TEST_TAHAP3_VALIDATE_' . $run['tag'] . '_' . $module;
        $payload = content_payload($case, $label);
        $reply = content_submit($run, $path, $payload, $module === 'documents');
        $row = content_row($pdo, $case, $label);
        content_require($reply['status'] === 200 && $row !== null, 'Validation fixture failed: ' . $module);
        $payload['id'] = (string) $row['id'];
        $badFields = [['sort_order'=>'7abc'], ['sort_order'=>'1e2'], ['sort_order'=>'1.5'],
            ['sort_order'=>'2147483648'], ['sort_order'=>['7']], ['is_active'=>'yes'], ['is_active'=>''],
            [$case['field']=>"\u{00a0}\u{2003}"], [$case['field']=>"bad\0value"]];
        if ($module === 'news') {
            $badFields = array_merge($badFields, [['slug'=>'../path'], ['slug'=>'has space'], ['slug'=>'slug?query'], ['slug'=>str_repeat('s',181)]]);
        }
        if (in_array($module, ['agenda','documents','achievements'], true)) {
            $badFields[] = ['academic_year'=>'2026/2026'];
        }
        if (in_array($module, ['agenda','announcements'], true)) {
            $badFields[] = ['starts_at'=>'2026-10-08T09:00','ends_at'=>'2026-10-07T09:00'];
        }
        foreach ($badFields as $index => $fields) {
            $before = content_state($pdo, $case['table'], $run);
            $reply = content_submit($run, $path, array_replace($payload, $fields), $module === 'documents' && !is_array(reset($fields)));
            content_check($reply['status'] === 200 && content_state($pdo, $case['table'], $run) === $before
                && str_contains($reply['body'], 'alert error'), $module . ' invalid field case ' . $index . ': graceful rejection before database/file write');
        }
        if (in_array($module, ['news','agenda','announcements'], true)) {
            $dateField = $module === 'news' ? 'published_at' : 'starts_at';
            $updated = array_replace($payload, [$dateField=>'2026-10-06T09:00:30']);
            if ($module === 'agenda') {
                $updated['ends_at'] = '2026-10-07T10:00:30';
            }
            $reply = content_submit($run, $path, $updated);
            $stored = content_row($pdo, $case, $label);
            content_check($reply['status'] === 200 && $stored[$dateField] === '2026-10-06 09:00:30', $module . ' valid datetime seconds retained');
        }
    }
    foreach ([['name'=>'   '], ['name'=>['x']], ['email'=>'bad-email'], ['home_teacher_limit'=>'3'],
        ['home_agenda_limit'=>'1e2'], ['theme_primary_color'=>'red'], ['current_academic_year'=>'2026/2026'],
        ['home_quick_links'=>'Title | Description | javascript:INERT'], ['home_quick_links'=>'Title only'],
        ['home_featured_areas'=>str_repeat('x',121) . ' | Desc | /profil'],
        ['nav_visible'=>['/profil'=>['1']]], ['home_show_agenda'=>'false']] as $index => $fields) {
        $before = content_state($pdo, 'site_settings', $run);
        $reply = content_submit($run, '/admin/settings.php', $fields);
        content_check($reply['status'] === 200 && content_state($pdo, 'site_settings', $run) === $before
            && str_contains($reply['body'], 'alert error'), 'settings invalid case ' . $index . ': no settings/file mutation');
    }
    $account = ['id'=>(string) $accounts['editor']['id'], 'username'=>$accounts['editor']['username'],
        'full_name'=>'Synthetic editor', 'role'=>'editor', 'password'=>'', 'is_active'=>'1'];
    foreach ([['role'=>'owner'], ['password'=>'short'], ['password'=>str_repeat('x',73)],
        ['username'=>str_repeat('x',51)], ['full_name'=>['x']], ['is_active'=>'yes']] as $index => $fields) {
        $before = content_state($pdo, 'admins', $run);
        $reply = content_submit($run, '/admin/admins.php', array_replace($account, $fields));
        content_check($reply['status'] === 200 && content_state($pdo, 'admins', $run) === $before, 'account invalid case ' . $index . ': hash/role unchanged');
    }
    $payload = ['name'=>'Visitor é','email'=>'visitor@example.invalid','phone'=>'','subject'=>'TEST_TAHAP3_VALIDATE_' . $run['tag'] . '_CONTACT',
        'message'=>str_repeat('Pesan normal é & <b>teks</b> ',200)];
    $reply = content_submit($anonymous, '/kontak', $payload);
    $row = content_row($pdo, ['table'=>'contact_messages','field'=>'subject'], $payload['subject']);
    content_check($reply['status'] === 200 && $row !== null && $row['message'] === trim($payload['message']), 'contact long unicode/HTML string saved without truncation');
    foreach ([['name'=>['x']], ['name'=>str_repeat('é',121)], ['email'=>'invalid'], ['message'=>"\u{00a0}"],
        ['message'=>str_repeat('é',32768)]] as $index => $fields) {
        $before = content_state($pdo, 'contact_messages', $run);
        $reply = content_submit($anonymous, '/kontak', array_replace($payload,$fields));
        content_check($reply['status'] === 200 && content_state($pdo, 'contact_messages', $run) === $before
            && str_contains($reply['body'], 'alert error'), 'contact invalid case ' . $index . ': no truncation/write');
    }
    foreach (['../dummy.pdf', str_repeat('x',256) . '.pdf'] as $index => $filename) {
        $before = content_state($pdo, 'site_settings', $run);
        $reply = content_submit($run, '/admin/settings.php', ['accreditation_file'=>new CURLFile($run['snapshot'] . '/fixture.pdf', 'application/pdf', $filename)]);
        content_check($reply['status'] === 200 && content_state($pdo,'site_settings',$run) === $before, 'invalid upload filename case ' . $index . ': no file/settings write');
        $ownedSettings = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
    }
}
