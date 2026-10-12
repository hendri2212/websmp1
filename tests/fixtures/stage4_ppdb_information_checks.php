<?php

declare(strict_types=1);

function stage4_ppdb_menu_checks(string $html, string $label, bool $informationAllowed): void
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    content_require($loaded, 'Cannot inspect rendered admin menu.');
    $xpath = new DOMXPath($document);
    $sidebar = $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " admin-nav ")]');
    content_check($sidebar->length === 1, $label . ': actual admin sidebar rendered');
    $informationLinks = 0;
    $legacyLinks = 0;
    foreach ($xpath->query('//a[@href] | //form[@action]') as $node) {
        $url = $node->getAttribute($node->tagName === 'a' ? 'href' : 'action');
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (preg_match('~/(?:ppdb_applications|ppdb_export|ppdb_download|ppdb_receipt)\.php|/ppdb/bukti~', $path)) {
            $legacyLinks++;
        }
    }
    foreach ($xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " admin-nav ")]//a[@href]') as $node) {
        if (str_ends_with(parse_url($node->getAttribute('href'), PHP_URL_PATH) ?: '', '/admin/ppdb.php')) {
            $informationLinks++;
        }
    }
    content_check($legacyLinks === 0, $label . ': no legacy link or form action anywhere in rendered page');
    content_check($informationLinks === ($informationAllowed ? 1 : 0), $label . ': information menu follows role permission');
    content_check(!str_contains($html, 'ppdb-donut') && !str_contains($html, 'total pendaftar'), $label . ': no registration status chart/counter');
}

function stage4_ppdb_information_checks(PDO $pdo, array $run, array $clients, array $anonymous, array &$ownedInformation): void
{
    $before = content_state($pdo, 'ppdb_applications', $run);
    $page = content_http($anonymous, '/ppdb');
    content_check($page['status'] === 200 && str_contains($page['body'], 'Dinas Pendidikan'), 'Public PPDB information loads with official registration responsibility');
    foreach (['name="student_name"', 'name="parent_email"', 'type="file"', 'Kirim Pendaftaran', 'ppdb/bukti'] as $forbidden) {
        content_check(!str_contains($page['body'], $forbidden), 'Public information excludes registration control: ' . $forbidden);
    }
    $privacy = content_http($anonymous, '/kebijakan-privasi');
    content_check($privacy['status'] === 200 && !str_contains($privacy['body'], 'Dengan mengirim formulir PPDB online'), 'Privacy no longer advertises local registration consent');
    foreach (['information','receipt','applications','export','download'] as $key) {
        foreach (['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'] as $method) {
            $process = proc_open([PHP_BINARY, __DIR__ . '/stage4_ppdb_direct_entry_probe.php', $key, $method], [1=>['pipe','w'], 2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
            content_require(is_resource($process), 'Cannot start direct entry point probe.');
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $result = json_decode($output, true);
            $status = $key === 'information' ? (in_array($method, ['GET','HEAD'], true) ? 404 : 405) : 410;
            content_check($exit === 0 && $errors === '' && is_array($result) && $result['status'] === $status, 'Standalone PHP entry point rejects ' . $key . '/' . $method . ' without errors');
        }
    }

    foreach (['anonymous'=>$anonymous] + $clients as $role => $client) {
        $payload = ['delete_id'=>'1', 'application_id'=>'1', 'status'=>'diterima', 'role'=>'superadmin'];
        if ($role !== 'anonymous') {
            $payload['csrf_token'] = content_token($client, '/admin/change_password.php');
        }
        $payload['family_card_file'] = new CURLFile($run['snapshot'] . '/fixture.pdf', 'application/pdf', 'TEST_TAHAP3_DISABLED.pdf');
        $reply = content_http($client, '/ppdb', $payload);
        content_check($reply['status'] === 405 && ($reply['headers']['allow'] ?? '') === 'GET, HEAD', $role . ': public registration POST rejected');
        foreach (['/ppdb/bukti', '/pages/ppdb_receipt.php', '/admin/ppdb_applications.php', '/admin/ppdb_export.php', '/admin/ppdb_download.php'] as $path) {
            foreach ([null, $payload] as $body) {
                $reply = content_http($client, $path . '?id=1&file=family_card&registration_number=TEST', $body);
                content_check($reply['status'] === 410 && str_contains($reply['body'], 'tidak tersedia'), $role . ': retired endpoint denies ' . ($body === null ? 'GET ' : 'POST ') . $path);
                content_check(!str_contains($reply['body'], 'TEST') && !str_contains($reply['body'], '%PDF') && !str_contains($reply['body'], '<form'), $role . ': retired endpoint returns no receipt, file, or form');
            }
        }
        content_check(content_state($pdo, 'ppdb_applications', $run) === $before, $role . ': retired requests create no applicant records or uploads');
        $informationBefore = content_state($pdo, 'ppdb_entries', $run);
        foreach ([
            '/ppdb?action=register&student_name=TEST&role=superadmin',
            '/ppdb/?registration_number=TEST&parent_email=TEST&status=diterima',
            '/ppdb?page=ppdb_applications&route=ppdb/bukti&download=1',
            '/ppdb?registration_number[]=TEST&id[]=1&action=delete',
            '/index.php?route=ppdb/bukti&page=ppdb_applications&action=register',
        ] as $path) {
            $reply = content_http($client, $path);
            content_check($reply['status'] === 200 && !preg_match('/name="(?:student_name|parent_email|application_id|registration_number)"|Kirim Pendaftaran/', $reply['body']), $role . ': query parameter cannot reopen registration: ' . $path);
            content_check(content_state($pdo, 'ppdb_applications', $run) === $before && content_state($pdo, 'ppdb_entries', $run) === $informationBefore, $role . ': query parameter does not mutate PPDB data');
        }
        foreach (['HEAD','PUT','PATCH','DELETE','OPTIONS'] as $method) {
            $reply = content_http($client, '/ppdb?role=superadmin&action=register', null, false, $method);
            content_check($reply['status'] === ($method === 'HEAD' ? 200 : 405), $role . ': information-only route handles ' . $method);
            foreach (['/admin/ppdb_applications.php','/admin/ppdb_export.php','/admin/ppdb_download.php','/ppdb/bukti/','/pages/ppdb_receipt.php'] as $path) {
                $reply = content_http($client, $path . '?action=register&role=superadmin&id=1', null, false, $method);
                // Routed receipts pass through the Stage 6 bootstrap method guard first.
                $routedReceipt = in_array($path, ['/ppdb/bukti/', '/pages/ppdb_receipt.php'], true);
                $expectedStatus = $routedReceipt && $method !== 'HEAD' ? 405 : 410;
                content_check($reply['status'] === $expectedStatus
                    && ($expectedStatus !== 405 || ($reply['headers']['allow'] ?? '') === 'GET, HEAD, POST'),
                    $role . ': retired route rejects ' . $method . ' ' . $path);
            }
        }
        foreach (['/pages/ppdb.php','/ppdb/daftar','/ppdb/register','/ppdb/upload','/ppdb/export','/ppdb/download','/admin/%70pdb_export.php','/admin/ppdb_applications.php/extra'] as $path) {
            foreach ([null, $payload] as $body) {
                $reply = content_http($client, $path . '?action=register&role=superadmin', $body);
                content_check(in_array($reply['status'], [403,404,405,410], true) && !str_contains($reply['body'], 'Kirim Pendaftaran'), $role . ': direct/unknown legacy alias cannot reopen workflow: ' . $path);
            }
        }
        $public = content_http($client, '/');
        content_check($public['status'] === 200 && !preg_match('~href="[^"]*(?:ppdb/bukti|ppdb_applications|ppdb_export|ppdb_download)[^"]*"~', $public['body']), $role . ': public menu has no registration link');
        content_check(content_state($pdo, 'ppdb_applications', $run) === $before && content_state($pdo, 'ppdb_entries', $run) === $informationBefore, $role . ': all alternate entry points leave PPDB records/uploads unchanged');
        if ($role !== 'anonymous') {
            $dashboard = content_http($client, '/admin/dashboard.php');
            content_require($dashboard['status'] === 200, 'Dashboard unavailable for menu audit.');
            stage4_ppdb_menu_checks($dashboard['body'], $role . ' dashboard', in_array($role, ['superadmin','ppdb'], true));
            $information = content_http($client, '/admin/ppdb.php');
            content_check($information['status'] === ($role === 'editor' ? 403 : 200), $role . ': direct information URL permission enforced');
            stage4_ppdb_menu_checks($information['body'], $role . ' information page', $role !== 'editor');
            if ($role !== 'editor') {
                content_check(str_contains($information['body'], 'Kelola Informasi PPDB') && !preg_match('/name="(?:student_name|application_id|registration_number|status|family_card_file)"|type="file"/', $information['body']), $role . ': editor contains only informative controls');
            }
        }
    }

    $payload = ['id'=>'', 'section'=>'informasi', 'title'=>'TEST_TAHAP3_INFO_' . $run['tag'], 'content'=>'Existing information test <b>not HTML</b>', 'sort_order'=>'1'];
    content_rejects($pdo, $run, $anonymous, $clients['editor'], '/admin/ppdb.php', 'ppdb_entries', $payload, 'Information permission and CSRF');
    foreach (['superadmin','ppdb'] as $role) {
        $client = $clients[$role];
        $state = content_state($pdo, 'ppdb_entries', $run);
        foreach (['123abc', '123.4', '1e2', '../123', "123\0", '001'] as $id) {
            $reply = content_http($client, '/admin/ppdb.php?edit=' . rawurlencode($id));
            content_check($reply['status'] === 400 && content_state($pdo, 'ppdb_entries', $run) === $state, $role . ': malformed information edit ID denied');
            $reply = content_submit($client, '/admin/ppdb.php', ['delete_id'=>$id]);
            content_check($reply['status'] === 400 && content_state($pdo, 'ppdb_entries', $run) === $state, $role . ': malformed information delete ID denied');
        }
        foreach (['section', 'title', 'content'] as $field) {
            foreach (['', '   ', ['not-scalar']] as $value) {
                $reply = content_submit($client, '/admin/ppdb.php', array_replace($payload, [$field=>$value]));
                content_check($reply['status'] === 200 && content_state($pdo, 'ppdb_entries', $run) === $state, $role . ': invalid information input cannot mutate data');
            }
        }
        $reply = content_submit($client, '/admin/ppdb.php', ['delete_id'=>'2147483647']);
        content_check($reply['status'] === 200 && str_contains($reply['body'], 'Data tidak ditemukan') && !str_contains($reply['body'], 'alert success'), $role . ': nonexistent information delete does not report success');
        $payload['id'] = '';
        $payload['content'] = 'Existing information test <b>not HTML</b>';
        $payload['title'] = 'TEST_TAHAP3_INFO_' . $run['tag'] . '_' . $role;
        $reply = content_submit($client, '/admin/ppdb.php', $payload);
        $statement = $pdo->prepare('SELECT * FROM ppdb_entries WHERE title = :title');
        $statement->execute(['title'=>$payload['title']]);
        $row = $statement->fetch();
        if ($row) {
            $ownedInformation[] = ['id'=>$row['id'], 'title'=>$row['title']];
        }
        content_check($reply['status'] === 200 && is_array($row), $role . ': existing information CREATE persists');
        content_require(is_array($row), 'Information create failed; stop before update.');
        $public = content_http($anonymous, '/ppdb');
        content_check($public['status'] === 200 && str_contains($public['body'], $payload['title']) && str_contains($public['body'], '&lt;b&gt;not HTML&lt;/b&gt;'), $role . ': existing information READ is public and escaped');
        $payload['id'] = (string) $row['id'];
        $payload['content'] = 'TEST_TAHAP3_UPDATED_' . $run['tag'];
        $reply = content_submit($client, '/admin/ppdb.php', $payload);
        $statement->execute(['title'=>$payload['title']]);
        $updated = $statement->fetch();
        $public = content_http($anonymous, '/ppdb');
        content_check($reply['status'] === 200 && $updated['id'] === $row['id'] && $updated['content'] === $payload['content'] && str_contains($public['body'], $payload['content']), $role . ': existing information UPDATE targets same row and appears publicly');
        $reply = content_submit($client, '/admin/ppdb.php', ['delete_id'=>(string) $row['id']]);
        $statement->execute(['title'=>$payload['title']]);
        content_check($reply['status'] === 200 && !$statement->fetch(), $role . ': existing information DELETE removes exact fixture');
        $dashboard = content_http($client, '/admin/dashboard.php');
        content_check($dashboard['status'] === 200 && !str_contains($dashboard['body'], 'ppdb_applications.php') && !str_contains($dashboard['body'], 'total pendaftar'), $role . ': dashboard and menu no longer expose applicants');
    }
    content_check(content_state($pdo, 'ppdb_applications', $run) === $before, 'Information CRUD does not change retained applicant data or upload files');
}
