<?php

declare(strict_types=1);

function input_restore(PDO $pdo, string $table, array $row): void
{
    $columns = array_keys($row);
    $quoted = array_map(static fn($column)=>'`' . $column . '`', $columns);
    $updates = array_map(static fn($column)=>'`' . $column . '`=VALUES(`' . $column . '`)', $columns);
    $statement = $pdo->prepare('INSERT INTO `' . $table . '` (' . implode(',', $quoted) . ') VALUES ('
        . implode(',', array_fill(0, count($columns), '?')) . ') ON DUPLICATE KEY UPDATE ' . implode(',', $updates));
    $statement->execute(array_values($row));
}

function input_fetch(PDO $pdo, string $table, int $id): ?array
{
    $statement = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function input_no_probe_nodes(string $html, string $marker): bool
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    return $xpath->query('//*[@data-stage3-probe="' . $marker . '"] | //script[contains(text(),"' . $marker . '")]')->length === 0;
}

function stage3_input_checks(PDO $pdo, array $run, array $anonymous, array $cases, array &$ownedSettings): void
{
    $setting = $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?)');
    $setting->execute(['siteAcademic', '{"currentYear":"2025/2026"}']);
    $ownedSettings[] = ['id'=>(int) $pdo->lastInsertId(), 'setting_key'=>'siteAcademic'];
    $required = [
        'news'=>['title','slug','category','excerpt','content'],
        'gallery'=>['title','description'], 'documents'=>['title','category','description'],
        'announcements'=>['title','content'], 'staff'=>['name','position','subject'],
        'agenda'=>['title','category','location','description','starts_at'],
        'achievements'=>['title','level','category','achiever_name','achievement_date','description'],
        'facilities'=>['name','category','condition_status','description'],
        'extracurriculars'=>['name','category','description'], 'faqs'=>['question','answer','category'],
    ];
    $dateFields = ['news'=>['published_at'], 'announcements'=>['starts_at','ends_at'],
        'agenda'=>['starts_at','ends_at'], 'achievements'=>['achievement_date']];
    foreach ($cases as $module=>$case) {
        $path = '/admin/' . $module . '.php';
        $label = 'TEST_TAHAP3_INPUT_' . $run['tag'] . '_' . $module;
        $payload = content_payload($case, $label);
        $reply = content_submit($run, $path, $payload, $module === 'documents');
        $original = content_row($pdo, $case, $label);
        content_require($reply['status'] === 200 && $original !== null, 'Input fixture creation failed: ' . $module);
        $id = (int) $original['id'];
        $payload['id'] = (string) $id;
        try {
            foreach ($required[$module] as $field) {
                foreach (['', '   '] as $empty) {
                    input_restore($pdo, $case['table'], $original);
                    $before = content_state($pdo, $case['table'], $run);
                    $reply = content_submit($run, $path, array_replace($payload, [$field=>$empty]));
                    content_check($reply['status'] < 500 && content_state($pdo, $case['table'], $run) === $before,
                        $module . ' required ' . $field . ' ' . ($empty === '' ? 'empty' : 'whitespace') . ': rejected without write (HTTP ' . $reply['status'] . ')');
                }
            }
            input_restore($pdo, $case['table'], $original);
            $before = content_state($pdo, $case['table'], $run);
            $reply = content_submit($run, $path, array_replace($payload, [$case['field']=>str_repeat('L', 5000)]));
            content_check(content_state($pdo, $case['table'], $run) === $before, $module . ' overlong text: no truncation/record mutation');
            content_check($reply['status'] < 500, $module . ' overlong text: graceful validation instead of server error (HTTP ' . $reply['status'] . ')');
            content_check(!preg_match('/SQLSTATE|Stack trace|PDOException|mysql:host|password_hash/i', $reply['body']), $module . ' overlong text: response contains no database/stack details');

            $special = $label . ' & " \' < >';
            $reply = content_submit($run, $path, array_replace($payload, [$case['field']=>$special]));
            $stored = input_fetch($pdo, $case['table'], $id);
            content_check($reply['status'] === 200 && $stored[$case['field']] === $special, $module . ' special characters: exact round trip through prepared write');
            $edit = content_http($run, $path . '?edit=' . $id);
            content_check(str_contains($edit['body'], htmlspecialchars($special, ENT_QUOTES, 'UTF-8')), $module . ' special characters: escaped in admin edit/list output');

            $marker = 'STAGE3_' . $module . '_' . substr($run['tag'], -12);
            $probe = '<b data-stage3-probe="' . $marker . '">HTML</b><script>/*' . $marker . '*/</script>';
            $htmlPayload = array_replace($payload, [$case['field']=>$probe]);
            foreach (['description','content','excerpt','bio','answer'] as $field) {
                if (array_key_exists($field, $htmlPayload)) {
                    $htmlPayload[$field] = $probe;
                }
            }
            $reply = content_submit($run, $path, $htmlPayload);
            content_check($reply['status'] === 200 && input_fetch($pdo, $case['table'], $id)[$case['field']] === $probe, $module . ' HTML/JS string: persists without altering text');
            $edit = content_http($run, $path . '?edit=' . $id);
            content_check(str_contains($edit['body'], htmlspecialchars($probe, ENT_QUOTES, 'UTF-8'))
                && input_no_probe_nodes($edit['body'], $marker), $module . ' HTML/JS: admin list/edit renders text, not probe DOM nodes');
            if ($module !== 'announcements') {
                $public = content_http($anonymous, $case['public']);
                content_check($public['status'] === 200 && str_contains($public['body'], htmlspecialchars($probe, ENT_QUOTES, 'UTF-8'))
                    && input_no_probe_nodes($public['body'], $marker), $module . ' HTML/JS: public list renders escaped text, not probe DOM nodes');
            }
            if ($module === 'news') {
                $detail = content_http($anonymous, '/berita/' . $payload['slug']);
                content_check($detail['status'] === 200 && str_contains($detail['body'], htmlspecialchars($probe, ENT_QUOTES, 'UTF-8'))
                    && input_no_probe_nodes($detail['body'], $marker), 'news HTML/JS: article body/detail escaped');
            }
            input_restore($pdo, $case['table'], $original);
            foreach ($dateFields[$module] ?? [] as $field) {
                foreach (['not-a-date','2026-02-30' . ($field === 'achievement_date' ? '' : 'T09:00')] as $date) {
                    $before = content_state($pdo, $case['table'], $run);
                    $reply = content_submit($run, $path, array_replace($payload, [$field=>$date]));
                    content_check(content_state($pdo, $case['table'], $run) === $before, $module . ' invalid date ' . $field . '=' . $date . ': no data mutation');
                    content_check($reply['status'] < 500, $module . ' invalid date ' . $field . ': graceful rejection (HTTP ' . $reply['status'] . ')');
                    input_restore($pdo, $case['table'], $original);
                }
            }
            if ($module === 'announcements' || $module === 'staff') {
                $field = $module === 'staff' ? 'email' : 'link_url';
                foreach ($module === 'staff' ? ['invalid-email'] : ['not a URL','javascript:/*STAGE3_INERT*/','https://example.invalid'] as $value) {
                    $before = content_state($pdo, $case['table'], $run);
                    $reply = content_submit($run, $path, array_replace($payload, [$field=>$value]));
                    $allowed = $value === 'https://example.invalid';
                    content_check($reply['status'] < 500 && ($allowed
                        ? input_fetch($pdo, $case['table'], $id)[$field] === $value
                        : content_state($pdo, $case['table'], $run) === $before), $module . ' ' . $field . '=' . $value . ': ' . ($allowed ? 'valid URL accepted' : 'invalid value rejected'));
                    input_restore($pdo, $case['table'], $original);
                }
            }
            foreach (['invalid', (string) $id . 'abc', (string) $id . '.5', '0', '-1','999999999'] as $badId) {
                $before = content_state($pdo, $case['table'], $run);
                $invalidPayload = array_replace($payload, ['id'=>$badId, $case['field']=>$label . '_INVALID_ID_CHANGED']);
                if ($module === 'news') {
                    $invalidPayload['slug'] = strtolower($label . '_id_' . str_replace(['.','-'], '_', $badId));
                }
                $reply = content_submit($run, $path, $invalidPayload);
                content_check($reply['status'] < 500 && content_state($pdo, $case['table'], $run) === $before, $module . ' invalid update ID ' . $badId . ': no accidental create/edit (HTTP ' . $reply['status'] . ')');
                input_restore($pdo, $case['table'], $original);
            }
            $before = content_state($pdo, $case['table'], $run);
            $reply = content_submit($run, $path, array_replace($payload, [$case['field']=>['not-scalar']]));
            content_check($reply['status'] < 500 && content_state($pdo, $case['table'], $run) === $before,
                $module . ' array instead of scalar text: safely rejected (HTTP ' . $reply['status'] . ')');
            foreach (['existing_image_path','existing_photo_path'] as $field) {
                if (!array_key_exists($field, $payload) || !isset($original[$field === 'existing_photo_path' ? 'photo_path' : 'image_path'])) {
                    continue;
                }
                $attribute = '/public/fixture.png" data-stage3-probe="' . $marker . '" onerror="/*' . $marker . '*/';
                $reply = content_submit($run, $path, array_replace($payload, [$field=>$attribute]));
                $storedPath = $field === 'existing_photo_path' ? 'photo_path' : 'image_path';
                content_check(input_fetch($pdo, $case['table'], $id)[$storedPath] === $original[$storedPath], $module . ' forged hidden image path: database path remains authoritative');
                $edit = content_http($run, $path . '?edit=' . $id);
                content_check($edit['status'] === 200 && input_no_probe_nodes($edit['body'], $marker), $module . ' existing image path: cannot break out of admin src attribute');
                $public = content_http($anonymous, $case['public']);
                content_check($public['status'] === 200 && input_no_probe_nodes($public['body'], $marker), $module . ' existing image path: cannot break out of public src attribute');
            }
            if ($module === 'news') {
                input_restore($pdo, $case['table'], $original);
                $reply = content_submit($run, $path, array_replace($payload, ['slug'=>'probe" data-stage3-probe="' . $marker . '" data-unused="']));
                $public = content_http($anonymous, '/berita');
                content_check($public['status'] === 200 && input_no_probe_nodes($public['body'], $marker), 'news slug: cannot break out of public href attribute');
            }
        } finally {
            input_restore($pdo, $case['table'], $original);
        }
    }
    stage3_input_contact_checks($pdo, $run, $anonymous);
    require_once __DIR__ . '/stage4_xss_checks.php';
    stage4_xss_checks($pdo, $run, $anonymous, $cases, $ownedSettings);
}

function stage3_input_contact_checks(PDO $pdo, array $run, array $anonymous): void
{
    $label = 'TEST_TAHAP3_INPUT_' . $run['tag'] . '_CONTACT';
    $payload = ['name'=>'Synthetic visitor','email'=>'synthetic@example.invalid','phone'=>'',
        'subject'=>$label,'message'=>'Synthetic message'];
    foreach (['name','email','subject','message'] as $field) {
        $before = content_state($pdo, 'contact_messages', $run);
        $reply = content_submit($anonymous, '/kontak', array_replace($payload, [$field=>'   ']));
        content_check($reply['status'] === 200 && content_state($pdo, 'contact_messages', $run) === $before,
            'contact required ' . $field . ': whitespace rejected without write');
    }
    $before = content_state($pdo, 'contact_messages', $run);
    $reply = content_submit($anonymous, '/kontak', array_replace($payload, ['email'=>'invalid-email']));
    content_check($reply['status'] === 200 && content_state($pdo, 'contact_messages', $run) === $before, 'contact invalid email rejected');
    $marker = 'STAGE3_CONTACT_' . substr($run['tag'], -12);
    $probe = '<b data-stage3-probe="' . $marker . '">HTML</b><script>/*' . $marker . '*/</script>';
    $reply = content_submit($anonymous, '/kontak', array_replace($payload, ['name'=>$probe,'message'=>$probe]));
    $row = content_row($pdo, ['table'=>'contact_messages','field'=>'subject'], $label);
    content_check($reply['status'] === 200 && $row !== null && $row['message'] === $probe, 'contact safe HTML/JS test message stored');
    $read = content_http($run, '/admin/contact_messages.php');
    content_check($read['status'] === 200 && str_contains($read['body'], htmlspecialchars($probe, ENT_QUOTES, 'UTF-8'))
        && input_no_probe_nodes($read['body'], $marker), 'contact HTML/JS escaped in admin message body');
    $reply = content_submit($run, '/admin/contact_messages.php', ['message_id'=>$row['id'],'status'=>'invalid-status']);
    content_check($reply['status'] < 500 && input_fetch($pdo, 'contact_messages', (int) $row['id'])['status'] === 'baru', 'contact invalid status does not alter message');
    $longPayload = array_replace($payload, ['subject'=>$label . '_LONG','name'=>str_repeat('N', 5000),'message'=>str_repeat('M', 5000)]);
    $before = content_state($pdo, 'contact_messages', $run);
    $reply = content_submit($anonymous, '/kontak', $longPayload);
    $long = content_row($pdo, ['table'=>'contact_messages','field'=>'subject'], $longPayload['subject']);
    content_check($reply['status'] < 500 && $long === null && content_state($pdo, 'contact_messages', $run) === $before,
        'contact overlong name: rejected without truncation or write');
}
