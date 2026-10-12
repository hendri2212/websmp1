<?php

declare(strict_types=1);

function upload_inventory(array $run): array
{
    $files = [];
    foreach (['public/uploads','storage/uploads'] as $folder) {
        $root = $run['snapshot'] . '/' . $folder;
        if (!is_dir($root)) {
            continue;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($run['snapshot']) + 1));
                $files[$relative] = hash_file('sha256', $file->getPathname());
            }
        }
    }
    ksort($files);
    return $files;
}

function upload_sensitive_hashes(array $run): array
{
    $hashes = [];
    foreach (['config/database.php','includes/auth.php','templates/header.php','.htaccess','public/uploads/.htaccess','storage/.htaccess'] as $file) {
        $hashes[$file] = hash_file('sha256', $run['snapshot'] . '/' . $file);
    }
    return $hashes;
}

function upload_target_request(PDO $pdo, array $run, array $target, array $spec, array &$ownedSettings, array $overrides = [], ?array $client = null, ?string $csrfOverride = null): array
{
    static $sequence = 0;
    $label = 'TEST_TAHAP3_UPLOAD_' . $run['tag'] . '_' . (++$sequence);
    $client ??= $run;
    if ($target['module'] === 'settings') {
        $payload = ['name'=>$label,'history_text'=>'Synthetic upload test','current_academic_year'=>'2025/2026'];
    } else {
        $payload = content_payload($target['case'], $label);
    }
    $payload = array_replace($payload, $overrides);
    $payload[$target['field']] = new CURLFile($spec['file'], $spec['mime'], $spec['name']);
    $payload['csrf_token'] = $csrfOverride ?? content_token($client, '/admin/' . $target['module'] . '.php');
    $reply = content_http($client, '/admin/' . $target['module'] . '.php', $payload);
    $row = null;
    $path = null;
    if ($target['module'] === 'settings') {
        $ownedSettings = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
        $values = [];
        foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $setting) {
            $values[$setting['setting_key']] = json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR);
        }
        if (($values['schoolProfile']['name'] ?? '') === $label) {
            $row = $values;
            $path = $values[$target['section']][$target['path_key']] ?? null;
        }
    } else {
        $row = content_row($pdo, $target['case'], $payload[$target['case']['field']]);
        $path = $row[$target['path_key']] ?? null;
    }
    return ['reply'=>$reply,'row'=>$row,'path'=>$path,'label'=>$label];
}

function upload_path_safe(array $run, array $target, ?string $path): bool
{
    if ($path === null) {
        return false;
    }
    $path = ltrim($path, '/');
    $folder = $target['kind'] === 'image' ? 'public/uploads/' : 'storage/uploads/';
    $pattern = '~^' . preg_quote($folder . $target['folder'] . '/' . $target['folder'], '~')
        . '-[0-9]{14}-[a-f0-9]{16}\.' . ($target['kind'] === 'image' ? '(webp|jpg|png)' : 'pdf') . '$~';
    $actual = realpath($run['snapshot'] . '/' . $path);
    $allowed = realpath($run['snapshot'] . '/' . $folder . $target['folder']);
    return preg_match($pattern, $path) === 1 && is_string($actual) && is_string($allowed)
        && str_starts_with($actual, $allowed . DIRECTORY_SEPARATOR) && is_file($actual);
}

function stage3_upload_checks(PDO $pdo, array $run, array $clients, array $anonymous, array $cases, array &$ownedSettings): void
{
    $targets = [];
    foreach (['news','gallery','staff','achievements','facilities','extracurriculars','documents'] as $module) {
        $targets[] = ['module'=>$module,'case'=>$cases[$module],'field'=>$module === 'staff' ? 'photo_file' : ($module === 'documents' ? 'document_file' : 'image_file'),
            'folder'=>$module,'kind'=>$module === 'documents' ? 'pdf' : 'image','path_key'=>$module === 'staff' ? 'photo_path' : ($module === 'documents' ? 'file_path' : 'image_path'),
            'table'=>$cases[$module]['table']];
    }
    foreach ([['school_logo_file','schoolProfile','logoPath','image','profile'], ['principal_photo_file','principalMessage','photoPath','image','profile'],
        ['accreditation_file','schoolAccreditation','filePath','pdf','accreditation']] as [$field,$section,$key,$kind,$folder]) {
        $targets[] = ['module'=>'settings','field'=>$field,'section'=>$section,'path_key'=>$key,'kind'=>$kind,'folder'=>$folder,'table'=>'site_settings'];
    }
    content_require(function_exists('imagecreatetruecolor') && function_exists('imagewebp'), 'GD image fixtures unavailable.');
    $fixtureDir = $run['snapshot'] . '/input-fixtures';
    mkdir($fixtureDir, 0700);
    $image = imagecreatetruecolor(640, 480);
    imagefill($image, 0, 0, imagecolorallocate($image, 40, 150, 80));
    imagestring($image, 5, 20, 20, 'TEST_TAHAP3_SAFE_IMAGE', imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $fixtureDir . '/valid.png');
    imagejpeg($image, $fixtureDir . '/valid.jpg', 85);
    imagewebp($image, $fixtureDir . '/valid.webp', 85);
    imagedestroy($image);
    $small = imagecreatetruecolor(200, 150);
    imagepng($small, $fixtureDir . '/small.png');
    imagedestroy($small);
    $safeScript = '<?php echo "TEST_TAHAP3_SAFE_EXECUTED"; ?>';
    file_put_contents($fixtureDir . '/script.txt', $safeScript);
    $png = (string) file_get_contents($fixtureDir . '/valid.png');
    file_put_contents($fixtureDir . '/header-only.png', substr($png, 0, 33) . $safeScript);
    file_put_contents($fixtureDir . '/image-trailer.png', $png . $safeScript);
    $jpeg = file_get_contents($fixtureDir . '/valid.jpg');
    $webp = file_get_contents($fixtureDir . '/valid.webp');
    file_put_contents($fixtureDir . '/truncated.jpg', substr($jpeg, 0, -60));
    file_put_contents($fixtureDir . '/truncated.webp', substr($webp, 0, -12));
    file_put_contents($fixtureDir . '/oversize.png', str_pad($png, 20 * 1048576 + 1, '0'));
    $pdf = (string) file_get_contents($run['snapshot'] . '/fixture.pdf');
    file_put_contents($fixtureDir . '/oversize.pdf', str_pad($pdf, 5 * 1048576 + 1, '0'));
    file_put_contents($fixtureDir . '/header-only.pdf', "%PDF-1.4\n" . $safeScript . "\n%%EOF\n");
    $sensitiveBefore = upload_sensitive_hashes($run);
    $staticSamples = [];
    $facts = [];

    foreach ($targets as $target) {
        $name = $target['module'] . '/' . $target['field'];
        $valid = $target['kind'] === 'image'
            ? [['valid.png','image/png'],['valid.jpg','image/jpeg'],['valid.webp','image/webp']]
            : [['../fixture.pdf','application/pdf']];
        foreach ($valid as [$input,$mime]) {
            $file = $target['kind'] === 'image' ? $fixtureDir . '/' . $input : $run['snapshot'] . '/fixture.pdf';
            $result = upload_target_request($pdo, $run, $target, ['file'=>$file,'mime'=>$mime,'name'=>'VALID_' . basename($input)], $ownedSettings);
            content_check($result['reply']['status'] === 200 && $result['row'] !== null && upload_path_safe($run, $target, $result['path']), $name . ' valid ' . $mime . ' persisted with safe server name/location');
            content_require($result['path'] !== null, 'Valid upload unavailable: ' . $name);
            $local = $run['snapshot'] . '/' . ltrim($result['path'], '/');
            $actualMime = (new finfo(FILEINFO_MIME_TYPE))->file($local);
            content_check($target['kind'] === 'image' ? in_array($actualMime, ['image/png','image/jpeg','image/webp'], true) && @getimagesize($local) !== false
                : $actualMime === 'application/pdf' && hash_file('sha256', $local) === hash('sha256', $pdf), $name . ' valid content remains readable and correct MIME');
            $url = $target['kind'] === 'image' ? '/' . ltrim($result['path'], '/')
                : ($target['module'] === 'documents' ? '/dokumen/unduh?id=' . $result['row']['id'] : '/akreditasi/file');
            $read = content_http($anonymous, $url);
            content_check($read['status'] === 200 && hash('sha256', $read['body']) === hash_file('sha256', $local), $name . ' HTTP read returns stored media bytes');
            if ($target['kind'] === 'image') {
                $thumb = dirname($local) . '/thumbs/' . basename($local);
                content_check(is_file($thumb) && @getimagesize($thumb) !== false, $name . ' valid thumbnail created');
                $staticSamples[] = '/' . ltrim($result['path'], '/');
            }
        }
        $validFile = $target['kind'] === 'image' ? $fixtureDir . '/valid.png' : $run['snapshot'] . '/fixture.pdf';
        $mime = $target['kind'] === 'image' ? 'image/png' : 'application/pdf';
        $extension = $target['kind'] === 'image' ? 'png' : 'pdf';
        $invalid = [
            'disallowed-extension'=>['file'=>$validFile,'mime'=>$mime,'name'=>'dummy.exe'],
            'MIME-mismatch'=>['file'=>$target['kind'] === 'image' ? $run['snapshot'] . '/fixture.pdf' : $fixtureDir . '/valid.png','mime'=>$mime,'name'=>'wrong.' . $extension],
            'oversize'=>['file'=>$fixtureDir . '/oversize.' . $extension,'mime'=>$mime,'name'=>'large.' . $extension],
            'path-traversal'=>['file'=>$validFile,'mime'=>$mime,'name'=>'../../config/database.php'],
            'double-extension'=>['file'=>$validFile,'mime'=>$mime,'name'=>'dummy.php.' . $extension],
            'script-disguised'=>['file'=>$fixtureDir . '/script.txt','mime'=>$mime,'name'=>'fake.' . $extension],
            'header-only-invalid-content'=>['file'=>$fixtureDir . '/header-only.' . $extension,'mime'=>$mime,'name'=>'header.' . $extension],
        ];
        if ($target['kind'] === 'image') {
            $invalid['small-image'] = ['file'=>$fixtureDir . '/small.png','mime'=>'image/png','name'=>'small.png'];
            foreach (['image.php.jpg','image.jpg.php','image..jpg','payload.phtml'] as $hostileName) {
                $invalid['explicit-name-' . $hostileName] = ['file'=>$fixtureDir . '/valid.png','mime'=>'image/png','name'=>$hostileName];
            }
            $invalid['PNG-content-JPEG-extension'] = ['file'=>$fixtureDir . '/valid.png','mime'=>'image/jpeg','name'=>'image.jpg'];
            $invalid['corrupt-JPEG'] = ['file'=>$fixtureDir . '/truncated.jpg','mime'=>'image/jpeg','name'=>'truncated.jpg'];
            $invalid['corrupt-WebP'] = ['file'=>$fixtureDir . '/truncated.webp','mime'=>'image/webp','name'=>'truncated.webp'];
        }
        foreach ($invalid as $scenario => $spec) {
            $before = content_state($pdo, $target['table'], $run);
            $filesBefore = upload_inventory($run);
            $result = upload_target_request($pdo, $run, $target, $spec, $ownedSettings);
            $rejected = $result['row'] === null && content_state($pdo, $target['table'], $run) === $before;
            content_check($result['reply']['status'] === 200 && $rejected, $name . ' rejects ' . $scenario . ' without record/file changes');
            if ($result['row'] === null) {
                content_check(upload_inventory($run) === $filesBefore, $name . ' rejected ' . $scenario . ' leaves no upload garbage');
            } else {
                content_check(upload_path_safe($run, $target, $result['path']) && upload_sensitive_hashes($run) === $sensitiveBefore, $name . ' accepted ' . $scenario . ' still contained; no sensitive overwrite');
                $facts[] = ['target'=>$name,'scenario'=>$scenario,'accepted'=>true,'safe_path'=>upload_path_safe($run, $target, $result['path'])];
                if ($target['kind'] === 'image') {
                    $staticSamples[] = '/' . ltrim($result['path'], '/');
                }
            }
        }
        $duplicates = [];
        foreach (['duplicate','duplicate','strange " name [test]'] as $clientName) {
            $result = upload_target_request($pdo, $run, $target, ['file'=>$validFile,'mime'=>$mime,'name'=>$clientName . '.' . $extension], $ownedSettings);
            content_check($result['reply']['status'] === 200 && upload_path_safe($run, $target, $result['path']), $name . ' duplicate/strange original name replaced safely');
            $duplicates[] = $result['path'];
        }
        $remainingDuplicates = array_values(array_filter($duplicates, static fn($path)=>is_file($run['snapshot'] . '/' . ltrim((string) $path, '/'))));
        content_check(count(array_unique($duplicates)) === 3
            && ($target['module'] === 'settings' ? $remainingDuplicates === [end($duplicates)] : count($remainingDuplicates) === 3),
            $name . ' duplicate client names stay unique; only superseded settings media is collected');
        if ($target['kind'] === 'image') {
            $result = upload_target_request($pdo, $run, $target, ['file'=>$fixtureDir . '/valid.png','mime'=>'application/octet-stream','name'=>'actual-image.png'], $ownedSettings);
            content_check($result['row'] !== null && upload_path_safe($run, $target, $result['path']), $name . ' actual PNG accepted independently of untrusted browser Content-Type');
            $result = upload_target_request($pdo, $run, $target, ['file'=>$fixtureDir . '/image-trailer.png','mime'=>'image/png','name'=>'safe-trailer.png'], $ownedSettings);
            $stored = $result['path'] !== null ? (string) file_get_contents($run['snapshot'] . '/' . ltrim($result['path'], '/')) : '';
            content_check($result['row'] !== null && !str_contains($stored, '<?php') && !str_contains($stored, 'TEST_TAHAP3_SAFE_EXECUTED'), $name . ' valid image with inert script trailer re-encoded without script bytes');
        }
        $before = content_state($pdo, $target['table'], $run);
        $spec = ['file'=>$validFile,'mime'=>$mime,'name'=>'valid.' . $extension];
        foreach (['csrf','ppdb','anonymous'] as $guard) {
            $client = $guard === 'ppdb' ? $clients['ppdb'] : ($guard === 'anonymous' ? $anonymous : $run);
            $token = $guard === 'csrf' ? 'invalid-token' : content_token($client, $guard === 'anonymous' ? '/admin/login.php' : '/admin/change_password.php');
            $result = upload_target_request($pdo, $run, $target, $spec, $ownedSettings, [], $client, $token);
            $expected = $guard === 'ppdb' ? 403 : ($guard === 'anonymous' ? 302 : 200);
            content_check($result['reply']['status'] === $expected && content_state($pdo, $target['table'], $run) === $before, $name . ' ' . $guard . ' rejection occurs before upload writes');
        }
        if (in_array($target['module'], ['staff','achievements','facilities','extracurriculars','documents'], true)) {
            $field = $target['case']['field'];
            $before = content_state($pdo, $target['table'], $run);
            $filesBefore = upload_inventory($run);
            $result = upload_target_request($pdo, $run, $target, $spec, $ownedSettings, [$field=>'']);
            content_check($result['row'] === null && upload_inventory($run) === $filesBefore && content_state($pdo, $target['table'], $run) === $before,
                $name . ' later form validation failure leaves no orphan files');
        }
        if ($target['module'] === 'news') {
            $existingSlug = $pdo->query('SELECT slug FROM news ORDER BY id LIMIT 1')->fetchColumn();
            $before = content_state($pdo, 'news', $run);
            $result = upload_target_request($pdo, $run, $target, $spec, $ownedSettings, ['slug'=>$existingSlug]);
            content_check($result['reply']['status'] === 500 && $result['reply']['body'] === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.',
                'news duplicate synthetic slug failure hides SQL/path/stack details');
            content_check(content_state($pdo, 'news', $run) === $before, 'news database save failure leaves no orphan upload files');
        }
    }

    $before = content_state($pdo, 'site_settings', $run);
    $payload = ['name'=>'TEST_TAHAP3_UPLOAD_' . $run['tag'] . '_MULTI_FAIL', 'csrf_token'=>content_token($run, '/admin/settings.php'),
        'school_logo_file'=>new CURLFile($fixtureDir . '/valid.png','image/png','logo.png'),
        'principal_photo_file'=>new CURLFile($fixtureDir . '/valid.png','image/png','principal.png'),
        'accreditation_file'=>new CURLFile($fixtureDir . '/script.txt','application/pdf','invalid.pdf')];
    $reply = content_http($run, '/admin/settings.php', $payload);
    content_check($reply['status'] === 200 && content_state($pdo, 'site_settings', $run) === $before, 'settings multi-upload failure rolls back earlier successful files');
    content_check(upload_sensitive_hashes($run) === $sensitiveBefore, 'All hostile client names leave sensitive project files unchanged');
    $staticSamples = array_values(array_unique($staticSamples));
    foreach ($staticSamples as $path) {
        $read = content_http($anonymous, $path);
        if (!is_file($run['snapshot'] . $path)) {
            content_check($read['status'] === 404, 'Preview denies superseded media after lifecycle cleanup');
        } else {
            content_check($read['status'] === 200 && hash('sha256', $read['body']) === hash_file('sha256', $run['snapshot'] . $path), 'Preview serves uploaded media bytes statically, not as PHP');
        }
    }
    file_put_contents($run['snapshot'] . '/upload-observations.json', json_encode(['targets'=>$targets,'accepted_cases'=>$facts], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    require __DIR__ . '/stage3_upload_apache_checks.php';
    stage3_upload_apache_checks($run, array_values(array_filter($staticSamples, static fn($path)=>is_file($run['snapshot'] . $path))));
}
