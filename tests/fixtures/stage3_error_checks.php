<?php

declare(strict_types=1);

function stage3_error_reply(array $reply, string $label, array $secrets, ?int $status = null, ?string $body = null): void
{
    if ($status !== null) {
        content_check($reply['status'] === $status, $label . ': expected HTTP ' . $status . ' (actual ' . $reply['status'] . ')');
    }
    if ($body !== null) {
        content_check($reply['body'] === $body, $label . ': exact generic response, no partial body');
    }
    $response = $reply['body'] . json_encode($reply['headers'], JSON_THROW_ON_ERROR);
    foreach ($secrets as $kind=>$needles) {
        $safe = true;
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($response, $needle)) {
                $safe = false;
                break;
            }
        }
        content_check($safe, $label . ': no ' . $kind . ' in response body/headers');
    }
    if ($status === 500) {
        content_check(($reply['headers']['content-type'] ?? '') === 'text/plain; charset=UTF-8'
            && ($reply['headers']['cache-control'] ?? '') === 'no-store'
            && ($reply['headers']['x-content-type-options'] ?? '') === 'nosniff', $label . ': generic 500 uses safe response headers');
    }
}

function stage3_error_checks(PDO $pdo, array $run, array $anonymous, array $cases, array &$ownedSettings, array &$accounts): void
{
    $connection = json_decode(file_get_contents(dirname($run['snapshot']) . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
    $secrets = [
        'SQL/query detail'=>['SQLSTATE','PDOException','SELECT STAGE3_COLUMN_DOES_NOT_EXIST','SELECT password_hash FROM admins','INSERT INTO news','mysql:host='],
        'credential/config detail'=>[$connection['password'], $connection['username'], $connection['database'], 'STAGE3_PRIVATE_SENTINEL_PASSWORD','STAGE3_PRIVATE_HOST','STAGE3_PRIVATE_DATABASE','STAGE3_PRIVATE_CONFIGURATION'],
        'stack trace'=>['Stack trace:', '#0 ', 'Uncaught ', 'Fatal error:', 'Warning:', 'TypeError:'],
        'sensitive filesystem path'=>[$run['snapshot'], dirname(__DIR__, 2), 'C:\\STAGE3_PRIVATE_PATH', '/var/www/', 'config/database.php on line'],
    ];
    $generic = 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.';
    foreach ($cases as $module=>$case) {
        $path = '/admin/' . $module . '.php';
        $reply = content_http($run, $path . '?edit=2147483647');
        stage3_error_reply($reply, $module . ' missing edit record', $secrets, 200);
        $before = content_state($pdo, $case['table'], $run);
        $reply = content_submit($run, $path, ['delete_id'=>'2147483647']);
        stage3_error_reply($reply, $module . ' missing delete record', $secrets, 200);
        content_check(content_state($pdo, $case['table'], $run) === $before, $module . ' missing delete: no record/file mutation');
        content_check(!str_contains($reply['body'], 'berhasil dihapus'), $module . ' missing delete: does not falsely claim successful deletion');
    }
    $reply = content_http($anonymous, '/berita/stage3_missing_record');
    stage3_error_reply($reply, 'Public missing article', $secrets, 404);
    foreach (['2147483647','invalid','-1'] as $id) {
        $reply = content_http($anonymous, '/dokumen/unduh?id=' . $id);
        stage3_error_reply($reply, 'Missing/invalid document ' . $id, $secrets, 404, 'Dokumen tidak ditemukan.');
    }
    $label = 'TEST_TAHAP3_ERRORS_' . $run['tag'];
    $statement = $pdo->prepare('INSERT INTO school_documents (title,category,description,file_path,original_filename) VALUES (?,?,?,?,?)');
    $statement->execute([$label . '_FILE','Synthetic','Synthetic missing file','storage/uploads/documents/STAGE3_MISSING.pdf','synthetic.pdf']);
    $missingId = (int) $pdo->lastInsertId();
    $reply = content_http($anonymous, '/dokumen/unduh?id=' . $missingId);
    stage3_error_reply($reply, 'Existing document with missing file', $secrets, 404, 'Dokumen tidak ditemukan.');
    $statement = $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?)');
    $statement->execute(['schoolAccreditation', json_encode(['filePath'=>'storage/uploads/accreditation/STAGE3_MISSING.pdf'], JSON_THROW_ON_ERROR)]);
    $ownedSettings[] = ['id'=>(int) $pdo->lastInsertId(), 'setting_key'=>'schoolAccreditation'];
    $reply = content_http($anonymous, '/akreditasi/file');
    stage3_error_reply($reply, 'Missing accreditation file', $secrets, 404, 'Lembar akreditasi tidak ditemukan.');

    $payload = content_payload($cases['news'], $label . '_NEWS');
    $reply = content_submit($run, '/admin/news.php', $payload);
    $original = content_row($pdo, $cases['news'], $payload['title']);
    content_require($reply['status'] === 200 && $original !== null, 'Synthetic error fixture unavailable.');
    $countBefore = (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
    $duplicate = array_replace($payload, ['title'=>$label . '_DUPLICATE']);
    $reply = content_submit($run, '/admin/news.php', $duplicate);
    stage3_error_reply($reply, 'Real INSERT failure: duplicate synthetic slug', $secrets, 500, $generic);
    content_check((int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn() === $countBefore
        && content_row($pdo, $cases['news'], $payload['title']) === $original, 'Duplicate SQL failure: original row/count unchanged');
    $edit = array_replace($payload, ['id'=>(string) $original['id']]);
    foreach (['overlong'=>['title'=>str_repeat('X', 5000)], 'invalid-date'=>['published_at'=>'not-a-date'],
        'wrong-type'=>['title'=>['not-scalar']]] as $scenario=>$override) {
        $before = content_state($pdo, 'news', $run);
        $reply = content_submit($run, '/admin/news.php', array_replace($edit, $override));
        stage3_error_reply($reply, 'Invalid input ' . $scenario, $secrets, 200);
        content_check(str_contains($reply['body'], '<div class="alert error">')
            && !str_contains($reply['body'], 'Berita berhasil disimpan.'), 'Invalid input ' . $scenario . ': explicit validation feedback, never success');
        content_check(content_row($pdo, $cases['news'], $payload['title']) === $original
            && content_state($pdo, 'news', $run) === $before, 'Invalid input ' . $scenario . ': all rows/files unchanged');
    }
    $reply = content_submit($run, '/admin/faqs.php', content_payload($cases['faqs'], ''));
    stage3_error_reply($reply, 'Graceful required-field validation', $secrets, 200);
    $unknownUsername = $run['tag'] . '_missing';
    $accounts['missing_login_log'] = ['id'=>0, 'username'=>$unknownUsername];
    $reply = content_submit($anonymous, '/admin/login.php', ['username'=>$unknownUsername,'password'=>'STAGE3_WRONG_PASSWORD']);
    stage3_error_reply($reply, 'Login account not found', $secrets, 200);
    $reply = content_submit($anonymous, '/admin/login.php', ['username'=>$accounts['superadmin']['username'],'password'=>'STAGE3_WRONG_PASSWORD']);
    stage3_error_reply($reply, 'Login wrong password', $secrets, 200);
    foreach (['query','buffered-exception','fatal','warning'] as $scenario) {
        $before = content_state($pdo, 'news', $run);
        $reply = content_http($anonymous, '/__stage3_error_probe?' . http_build_query(['key'=>$run['tag'],'scenario'=>$scenario]));
        stage3_error_reply($reply, 'Isolated HTTP probe ' . $scenario, $secrets, $scenario === 'warning' ? 200 : 500,
            $scenario === 'warning' ? '{"ok":true,"display_errors":"0","display_startup_errors":"0"}' : $generic);
        content_check(content_state($pdo, 'news', $run) === $before, 'Isolated HTTP probe ' . $scenario . ': no data/file mutation');
    }
    foreach (['/server.err.log','/server.out.log','/config/database.php','/manifest.json','/error-probe.php'] as $path) {
        $reply = content_http($anonymous, $path);
        stage3_error_reply($reply, 'Private testing resource ' . $path, $secrets, 403);
    }
    $log = file_get_contents($run['snapshot'] . '/server.err.log');
    content_check(str_contains($log, 'Unhandled PDOException') && str_contains($log, 'Unhandled RuntimeException'), 'Internal exception logging retained without exposing HTTP diagnostics');
    content_check(!str_contains($log, $connection['password']), 'Internal log contains no actual testing database password');
}
