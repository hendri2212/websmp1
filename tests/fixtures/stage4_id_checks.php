<?php

declare(strict_types=1);

function stage4_bad_ids(int $id): array
{
    return [$id . 'abc', $id . '.4', '1e2', '../' . $id, $id . '%00', $id . "\0", '00' . $id,
        '0', '-1', '+' . $id, ' ' . $id, $id . ' ', $id . "\n", '', [(string) $id], str_repeat('9', 40)];
}

function stage4_id_checks(PDO $pdo, array $run, array $anonymous, array $cases, array $accounts): void
{
    foreach ($cases as $module => $case) {
        $path = '/admin/' . $module . '.php';
        $label = 'TEST_TAHAP3_ID_' . $run['tag'] . '_' . $module;
        $payload = content_payload($case, $label);
        $created = content_submit($run, $path, $payload, $module === 'documents');
        $row = content_row($pdo, $case, $label);
        content_require($created['status'] === 200 && $row !== null, 'ID fixture creation failed: ' . $module);
        $id = (int) $row['id'];
        $read = content_http($run, $path . '?edit=' . $id);
        content_check($read['status'] === 200 && str_contains($read['body'], 'value="' . $id . '"'), $module . ' canonical ID accepted');
        foreach (stage4_bad_ids($id) as $index => $bad) {
            $before = content_state($pdo, $case['table'], $run);
            $query = http_build_query(['edit'=>$bad]);
            $read = content_http($run, $path . '?' . $query);
            content_check($read['status'] === 400 && content_state($pdo, $case['table'], $run) === $before,
                $module . ' strict GET edit case ' . $index . ': 400/no mutation');
            if ($bad !== '') {
                // libcurl does not encode nested arrays as PHP form arrays in multipart fields.
                $reply = content_submit($run, $path, array_replace($payload, ['id'=>$bad]), $module === 'documents' && !is_array($bad));
                content_check($reply['status'] === 400 && content_state($pdo, $case['table'], $run) === $before,
                    $module . ' strict POST update case ' . $index . ': 400/no database/file mutation');
            }
            $reply = content_submit($run, $path, ['delete_id'=>$bad]);
            content_check($reply['status'] === 400 && content_state($pdo, $case['table'], $run) === $before,
                $module . ' strict POST delete case ' . $index . ': 400/no mutation');
            if (in_array($module, ['documents','achievements'], true)) {
                $url = $module === 'documents' ? '/dokumen/unduh?' . http_build_query(['id'=>$bad])
                    : '/prestasi/' . rawurlencode(is_string($bad) ? $bad : 'Array');
                $reply = content_http($anonymous, $url);
                $expectedStatus = $module === 'achievements' && $bad === '' ? 200 : 404;
                content_check($reply['status'] === $expectedStatus && content_state($pdo, $case['table'], $run) === $before,
                    $module . ' public strict ID case ' . $index . ': safe response/no mutation');
            }
        }
        $edited = content_submit($run, $path, array_replace($payload, ['id'=>(string) $id, $case['field']=>$label . '_EDIT']));
        content_check($edited['status'] === 200 && content_row($pdo, $case, $label . '_EDIT') !== null, $module . ' canonical update retained');
        $deleted = content_submit($run, $path, ['delete_id'=>(string) $id]);
        content_check($deleted['status'] === 200 && content_row($pdo, $case, $label . '_EDIT') === null, $module . ' canonical delete retained');
    }
    $path = '/admin/admins.php';
    $id = $accounts['editor']['id'];
    foreach (stage4_bad_ids($id) as $index => $bad) {
        $before = content_state($pdo, 'admins', $run);
        $reply = content_http($run, $path . '?' . http_build_query(['edit'=>$bad]));
        content_check($reply['status'] === 400 && content_state($pdo, 'admins', $run) === $before, 'accounts strict GET case ' . $index);
        foreach ($bad === '' ? ['delete_id'] : ['id','delete_id'] as $key) {
            $reply = content_submit($run, $path, [$key=>$bad]);
            content_check($reply['status'] === 400 && content_state($pdo, 'admins', $run) === $before, 'accounts strict POST ' . $key . ' case ' . $index);
        }
    }
    $label = 'TEST_TAHAP3_ID_' . $run['tag'] . '_MESSAGE';
    $reply = content_submit($anonymous, '/kontak', ['name'=>'Synthetic visitor','email'=>'test@example.invalid',
        'phone'=>'','subject'=>$label,'message'=>'Synthetic message']);
    $row = content_row($pdo, ['table'=>'contact_messages','field'=>'subject'], $label);
    content_require($reply['status'] === 200 && $row !== null, 'Message ID fixture failed');
    foreach (stage4_bad_ids((int) $row['id']) as $index => $bad) {
        $before = content_state($pdo, 'contact_messages', $run);
        foreach (['message_id','delete_id'] as $key) {
            $reply = content_submit($run, '/admin/contact_messages.php', [$key=>$bad,'status'=>'selesai']);
            content_check($reply['status'] === 400 && content_state($pdo, 'contact_messages', $run) === $before,
                'messages strict POST ' . $key . ' case ' . $index);
        }
    }
}
