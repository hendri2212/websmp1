<?php

declare(strict_types=1);

function stage5_backup_expected(PDO $pdo, array $run): array
{
    $expected = ['tables'=>[], 'files'=>[], 'schema_hash'=>hash_file('sha256', $run['snapshot'] . '/database/schema.sql')];
    foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as $table) {
        $name = $table[0];
        content_require((bool) preg_match('/\A[a-zA-Z0-9_]+\z/', $name), 'Unexpected testing table identifier.');
        $expected['tables'][$name] = [
            'schema'=>$pdo->query('SHOW CREATE TABLE `' . $name . '`')->fetch()['Create Table'],
            'rows'=>$pdo->query('SELECT * FROM `' . $name . '`')->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
    foreach (['public/uploads', 'storage/uploads'] as $folder) {
        $directory = $run['snapshot'] . '/' . $folder;
        if (!is_dir($directory)) { continue; }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            content_require(!$file->isLink(), 'Unexpected fixture upload symlink.');
            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($run['snapshot']) + 1));
                $expected['files']['files/' . $relative] = hash_file('sha256', $file->getPathname());
            }
        }
    }
    return $expected;
}

function stage5_backup_completeness_checks(PDO $pdo, array $run, array $cases, array &$ownedSettings): void
{
    $initialSchema = stage5_backup_expected($pdo, $run)['tables'];
    $image = imagecreatetruecolor(640, 480);
    imagefill($image, 0, 0, imagecolorallocate($image, 40, 160, 90));
    content_require(imagepng($image, $run['snapshot'] . '/fixture-backup.png'), 'Cannot create synthetic image.');
    imagedestroy($image);
    $special = "Konten sintetis: apostrof ', koma, backslash \\, baris\nberikutnya, <b>teks</b>, "
        . json_decode('"\u00e9\u4e2d"', true, 512, JSON_THROW_ON_ERROR);
    foreach ($cases as $module => $case) {
        foreach ([1, 2] as $number) {
            $label = 'TEST_TAHAP3_BACKUP_' . $run['tag'] . '_' . $case['label'] . '_' . $number;
            $payload = content_payload($case, $label);
            foreach (['content','description','bio','answer'] as $field) {
                if (array_key_exists($field, $payload)) { $payload[$field] = $special; }
            }
            if ($number === 1) { $payload['is_active'] = '1'; }
            else { unset($payload['is_active']); }
            if (in_array($module, ['news','gallery','staff'], true)) {
                $field = $module === 'staff' ? 'photo_file' : 'image_file';
                $payload[$field] = new CURLFile($run['snapshot'] . '/fixture-backup.png', 'image/png', 'TEST_TAHAP3_BACKUP.png');
            }
            $reply = content_submit($run, '/admin/' . $module . '.php', $payload, $module === 'documents');
            $row = content_row($pdo, $case, $label);
            content_check($reply['status'] === 200 && $row !== null, $module . ': synthetic backup record ' . $number . ' created via admin handler');
            content_require($row !== null, 'Missing dataset for backup: ' . $module);
            if ($module === 'gallery') {
                content_check(!array_key_exists('is_active', $row), 'gallery: existing schema has no publication-status column');
            } else {
                content_check((int) $row['is_active'] === ($number === 1 ? 1 : 0), $module . ': active/inactive fixture status matches normal checkbox form');
            }
        }
    }
    $payload = ['name'=>'TEST_TAHAP3_BACKUP_PROFIL_' . $run['tag'], 'history_text'=>$special,
        'vision_text'=>'Visi sintetis', 'mission_text'=>'Misi sintetis',
        'home_hero_title'=>'TEST_TAHAP3_BACKUP_HOME_' . $run['tag'],
        'current_academic_year'=>'2026/2027', 'home_show_announcements'=>'1'];
    $reply = content_submit($run, '/admin/settings.php', $payload);
    $ownedSettings = $pdo->query('SELECT id, setting_key FROM site_settings ORDER BY id')->fetchAll();
    $profile = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'schoolProfile'")->fetchColumn();
    content_check($reply['status'] === 200 && is_string($profile)
        && json_decode($profile, true, 512, JSON_THROW_ON_ERROR)['name'] === $payload['name'], 'Synthetic settings/profile stored via admin handler');
    content_check((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 3, 'Three synthetic testing accounts included; no production accounts');
    content_check((int) $pdo->query('SELECT COUNT(*) FROM ppdb_applications')->fetchColumn() === 0, 'Legacy PPDB table empty: no applicant fixture or real student data');

    $ledger = [];
    foreach (['sql', 'full'] as $type) {
        $token = content_token($run, '/admin/db_backup.php');
        // Each export records its audit event after building the backup. Snapshot each export separately.
        $expected = stage5_backup_expected($pdo, $run);
        $expectedPath = $run['snapshot'] . '/backup-completeness-' . $type . '-expected.json';
        file_put_contents($expectedPath, json_encode($expected, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $response = content_http($run, '/admin/db_backup.php', ['backup_type'=>$type, 'csrf_token'=>$token]);
        $artifact = $run['snapshot'] . '/backup-completeness-' . $type . ($type === 'sql' ? '.sql' : '.zip');
        file_put_contents($artifact, $response['body']);
        content_check($response['status'] === 200 && str_contains($response['headers']['content-disposition'] ?? '', 'attachment;'), $type . ': real application POST produces backup attachment');
        $command = [PHP_BINARY, '-d', 'extension=' . dirname(PHP_BINARY) . '/ext/php_zip.dll',
            __DIR__ . '/stage5_backup_completeness_probe.php', $expectedPath, $artifact, $type];
        $process = proc_open($command, [1=>['pipe','w'], 2=>['file',$run['snapshot'] . '/backup-completeness-probe.err.log','a']], $pipes);
        content_require(is_resource($process), 'Cannot start read-only backup verifier.');
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exit = proc_close($process);
        $result = json_decode($output, true);
        content_require(is_array($result) && isset($result['checks']), 'Backup verifier produced no structured result.');
        foreach ($result['checks'] as $check) { content_check($check['passed'], $type . ': ' . $check['label']); }
        content_check($exit === 0, $type . ': independent read-only parser/file verifier passes');
        $ledger[$type] = ['sha256'=>hash_file('sha256', $artifact), 'bytes'=>filesize($artifact),
            'table_rows'=>array_map(static fn($table)=>count($table['rows']), $expected['tables']),
            'upload_files'=>count($expected['files']), 'verification'=>$result];
    }
    $temporary = glob($run['snapshot'] . '/storage/backups/.temporary/*.zip') ?: [];
    content_check($temporary === [], 'Application temporary ZIP removed after successful download');
    $finalSchema = stage5_backup_expected($pdo, $run)['tables'];
    $sameSchema = array_keys($initialSchema) === array_keys($finalSchema);
    foreach ($initialSchema as $name => $table) {
        // Inserted fixtures advance counters; compare structure, not the next AUTO_INCREMENT value.
        $sameSchema = $sameSchema && preg_replace('/ AUTO_INCREMENT=\d+/', '', $table['schema'])
            === preg_replace('/ AUTO_INCREMENT=\d+/', '', $finalSchema[$name]['schema']);
    }
    content_check($sameSchema, 'Every database table structure unchanged; only fixture AUTO_INCREMENT counters advance');
    file_put_contents($run['snapshot'] . '/backup-completeness-ledger.json', json_encode($ledger, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    // Downloaded artifacts/expected rows contain synthetic password hashes and the private test marker.
    foreach (glob($run['snapshot'] . '/backup-completeness-*') as $file) { chmod($file, 0600); }
}
