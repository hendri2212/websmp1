<?php

declare(strict_types=1);

function consistency_row(PDO $pdo, string $table, int $id): ?array
{
    $statement = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function consistency_files(array $run, string $path): array
{
    $path = ltrim($path, '/');
    $files = [$path];
    $thumbnail = dirname($path) . '/thumbs/' . basename($path);
    if (is_file($run['snapshot'] . '/' . $thumbnail)) {
        $files[] = $thumbnail;
    }
    return $files;
}

function consistency_scan(PDO $pdo, array $run, array $cases): array
{
    $references = [];
    $missing = [];
    $add = static function (string $path, string $owner) use (&$references, &$missing, $run): void {
        $path = ltrim($path, '/');
        if (!str_starts_with($path, 'public/uploads/') && !str_starts_with($path, 'storage/uploads/')) {
            return;
        }
        $references[$path][] = $owner;
        if (!is_file($run['snapshot'] . '/' . $path)) {
            $missing[] = ['owner'=>$owner,'path'=>$path];
        }
        $references[dirname($path) . '/thumbs/' . basename($path)][] = $owner . ':thumbnail';
    };
    foreach ($cases as $case) {
        foreach ($pdo->query('SELECT * FROM `' . $case['table'] . '`')->fetchAll() as $row) {
            foreach (['image_path','photo_path','file_path'] as $field) {
                if (isset($row[$field])) {
                    $add($row[$field], $case['table'] . ':' . $row['id']);
                }
            }
        }
    }
    foreach ($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $setting) {
        $value = json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR);
        $walk = static function ($node) use (&$walk, $add, $setting): void {
            if (is_array($node)) {
                foreach ($node as $child) {
                    $walk($child);
                }
            } elseif (is_string($node)) {
                $add($node, 'setting:' . $setting['setting_key']);
            }
        };
        $walk($value);
    }
    $inventory = upload_inventory($run);
    $orphans = array_filter(array_keys($inventory), static fn($path)=>!isset($references[$path]) && preg_match('/\.(png|jpg|jpeg|webp|pdf)$/i', $path));
    return ['missing'=>array_values($missing), 'orphan_files'=>array_values($orphans), 'references'=>$references, 'inventory'=>$inventory];
}

function stage3_consistency_checks(PDO $pdo, array $run, array $anonymous, array $cases, array &$ownedSettings): void
{
    content_require(function_exists('imagecreatetruecolor'), 'GD fixture unavailable.');
    $image = imagecreatetruecolor(640, 480);
    imagefill($image, 0, 0, imagecolorallocate($image, 35, 150, 90));
    $imageFile = $run['snapshot'] . '/consistency-fixture.png';
    imagepng($image, $imageFile);
    imagedestroy($image);
    $ledger = [];
    $relations = $pdo->prepare('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL');
    $relations->execute(['website_sekolah_test_stage3']);
    $ledger['declared_foreign_keys_testing'] = $relations->fetchAll();
    foreach ($cases as $module=>$case) {
        $path = '/admin/' . $module . '.php';
        $hasFile = in_array($module, ['news','gallery','documents','staff','achievements','facilities','extracurriculars'], true);
        $field = $module === 'staff' ? 'photo_file' : ($module === 'documents' ? 'document_file' : 'image_file');
        $pathKey = $module === 'staff' ? 'photo_path' : ($module === 'documents' ? 'file_path' : 'image_path');
        $upload = static function (array $payload) use ($hasFile, $field, $module, $run, $imageFile): array {
            if ($hasFile) {
                $payload[$field] = new CURLFile($module === 'documents' ? $run['snapshot'] . '/fixture.pdf' : $imageFile,
                    $module === 'documents' ? 'application/pdf' : 'image/png', 'TEST_TAHAP3_CONSISTENCY.' . ($module === 'documents' ? 'pdf' : 'png'));
            }
            return $payload;
        };
        $label = 'TEST_TAHAP3_CONSISTENCY_' . $run['tag'] . '_' . $module;
        $payload = content_payload($case, $label);
        content_submit($run, $path, $upload($payload));
        $row = content_row($pdo, $case, $label);
        content_require($row !== null, 'Consistency fixture missing: ' . $module);
        $guardPayload = content_payload($case, $label . '_GUARD');
        content_submit($run, $path, $upload($guardPayload));
        $guard = content_row($pdo, $case, $label . '_GUARD');
        content_require($guard !== null, 'Consistency guard missing: ' . $module);
        $countBefore = (int) $pdo->query('SELECT COUNT(*) FROM `' . $case['table'] . '`')->fetchColumn();
        $edit = array_replace($payload, ['id'=>(string) $row['id'], $case['field']=>$label . '_EDIT']);
        if ($hasFile && $module !== 'documents') {
            $edit[$module === 'staff' ? 'existing_photo_path' : 'existing_image_path'] = $row[$pathKey];
        }
        foreach (range(1, 3) as $repeat) {
            $reply = content_submit($run, $path, $edit);
            content_check($reply['status'] === 200 && (int) $pdo->query('SELECT COUNT(*) FROM `' . $case['table'] . '`')->fetchColumn() === $countBefore,
                $module . ' repeated UPDATE ' . $repeat . ': does not insert duplicate records');
        }
        content_check(consistency_row($pdo, $case['table'], (int) $guard['id']) === $guard, $module . ' repeated UPDATE: guard record unchanged');
        if ($hasFile) {
            content_check(consistency_row($pdo, $case['table'], (int) $row['id'])[$pathKey] === $row[$pathKey], $module . ' text-only UPDATE: existing upload reference preserved');
            $oldFiles = consistency_files($run, $row[$pathKey]);
            $reply = content_submit($run, $path, $upload($edit));
            $current = consistency_row($pdo, $case['table'], (int) $row['id']);
            content_check($reply['status'] === 200 && $current[$pathKey] !== $row[$pathKey]
                && is_file($run['snapshot'] . '/' . ltrim($current[$pathKey], '/')), $module . ' replace upload: same record points to new existing file');
            $oldRemain = array_values(array_filter($oldFiles, static fn($file)=>is_file($run['snapshot'] . '/' . $file)));
            content_check($oldRemain === [], $module . ' replace upload: obsolete file/thumbnail removed');
            $ledger['modules'][$module]['obsolete_after_replace'] = $oldRemain;
            $newFiles = consistency_files($run, $current[$pathKey]);
            if ($module === 'documents') {
                // Simulate loss of an owned synthetic file only, then restore it immediately.
                $local = realpath($run['snapshot'] . '/' . ltrim($current[$pathKey], '/'));
                $allowed = realpath($run['snapshot'] . '/storage/uploads/documents');
                content_require(is_string($local) && is_string($allowed)
                    && str_starts_with(strtolower($local), strtolower($allowed . DIRECTORY_SEPARATOR)) && is_file($local), 'Refuse to remove file outside isolated document fixture directory.');
                $bytes = file_get_contents($local);
                content_require(unlink($local), 'Owned synthetic file removal failed.');
                try {
                    $drift = consistency_scan($pdo, $run, $cases);
                    $reply = content_http($anonymous, '/dokumen/unduh?id=' . $current['id']);
                    content_check(count($drift['missing']) === 1 && consistency_row($pdo, $case['table'], (int) $current['id']) !== null
                        && $reply['status'] === 404, 'Simulated missing document: dangling file reference detected; download denies safely');
                    $ledger['controlled_file_loss'] = $drift['missing'];
                } finally {
                    file_put_contents($local, $bytes);
                }
            }
        }
        $reply = content_submit($run, $path, ['delete_id'=>$row['id']]);
        content_check($reply['status'] === 200 && consistency_row($pdo, $case['table'], (int) $row['id']) === null, $module . ' DELETE: intended database record removed');
        content_check(consistency_row($pdo, $case['table'], (int) $guard['id']) === $guard, $module . ' DELETE: guard record/references unchanged');
        if ($hasFile) {
            $remaining = array_values(array_filter($newFiles, static fn($file)=>is_file($run['snapshot'] . '/' . $file)));
            content_check($remaining === [], $module . ' DELETE: referenced file/thumbnail removed');
            $ledger['modules'][$module]['left_after_delete'] = $remaining;
            $guardHashes = [];
            foreach (consistency_files($run, $guard[$pathKey]) as $file) {
                $guardHashes[$file] = hash_file('sha256', $run['snapshot'] . '/' . $file);
            }
            content_check(!in_array(false, $guardHashes, true), $module . ' DELETE: guard files still exist');
            if (in_array($module, ['staff','achievements','facilities','extracurriculars','documents','news'], true)) {
                $before = upload_inventory($run);
                $bad = content_payload($case, $label . '_FAILED');
                if ($module === 'news') {
                    $bad['slug'] = $guard['slug'];
                } else {
                    $bad['category'] = '';
                    if ($module === 'staff') {
                        $bad['position'] = '';
                    }
                }
                $reply = content_submit($run, $path, $upload($bad));
                content_check(content_row($pdo, $case, $bad[$case['field']]) === null, $module . ' failed save: no unintended record created');
                $newOrphans = array_values(array_diff(array_keys(upload_inventory($run)), array_keys($before)));
                content_check($newOrphans === [], $module . ' failed save: uploaded file/thumbnail rolled back');
                $ledger['modules'][$module]['left_after_failed_save'] = $newOrphans;
            }
        }
    }
    foreach ([['school_logo_file','schoolProfile','logoPath','image','profile'],
        ['principal_photo_file','principalMessage','photoPath','image','profile'],
        ['accreditation_file','schoolAccreditation','filePath','pdf','accreditation']] as [$field,$section,$key,$kind,$folder]) {
        $target = ['module'=>'settings','field'=>$field,'section'=>$section,'path_key'=>$key,'kind'=>$kind,'folder'=>$folder];
        $spec = ['file'=>$kind === 'pdf' ? $run['snapshot'] . '/fixture.pdf' : $imageFile,
            'mime'=>$kind === 'pdf' ? 'application/pdf' : 'image/png', 'name'=>'TEST_TAHAP3_CONSISTENCY.' . ($kind === 'pdf' ? 'pdf' : 'png')];
        $first = upload_target_request($pdo, $run, $target, $spec, $ownedSettings);
        content_require($first['path'] !== null, 'Settings first upload unavailable: ' . $field);
        $previous = consistency_files($run, $first['path']);
        $keysBefore = $pdo->query('SELECT id,setting_key FROM site_settings ORDER BY id')->fetchAll();
        $second = upload_target_request($pdo, $run, $target, $spec, $ownedSettings);
        content_check($second['reply']['status'] === 200 && $second['path'] !== $first['path']
            && is_file($run['snapshot'] . '/' . ltrim($second['path'], '/')), 'settings ' . $field . ' replacement: new reference points to existing file');
        content_check($pdo->query('SELECT id,setting_key FROM site_settings ORDER BY id')->fetchAll() === $keysBefore, 'settings ' . $field . ' UPDATE: no duplicate setting rows');
        $remaining = array_values(array_filter($previous, static fn($file)=>is_file($run['snapshot'] . '/' . $file)));
        content_check($remaining === [], 'settings ' . $field . ' replacement: obsolete upload/thumbnail removed');
        $ledger['settings'][$field]['obsolete_after_replace'] = $remaining;
    }
    $badFile = $run['snapshot'] . '/consistency-invalid-image.txt';
    file_put_contents($badFile, 'TEST_TAHAP3_HARMLESS_NOT_AN_IMAGE');
    $beforeFiles = upload_inventory($run);
    $beforeSettings = $pdo->query('SELECT * FROM site_settings ORDER BY id')->fetchAll();
    $reply = content_submit($run, '/admin/settings.php', ['name'=>'TEST_TAHAP3_CONSISTENCY_' . $run['tag'] . '_FAILED_SETTINGS',
        'school_logo_file'=>new CURLFile($imageFile, 'image/png', 'valid.png'),
        'principal_photo_file'=>new CURLFile($badFile, 'text/plain', 'invalid.png')]);
    content_check($reply['status'] === 200 && $pdo->query('SELECT * FROM site_settings ORDER BY id')->fetchAll() === $beforeSettings,
        'settings mixed valid/invalid uploads: rejected save leaves database values unchanged');
    $newOrphans = array_values(array_diff(array_keys(upload_inventory($run)), array_keys($beforeFiles)));
    content_check($newOrphans === [], 'settings failed save: earlier successful upload/thumbnail rolled back');
    $ledger['settings']['left_after_failed_save'] = $newOrphans;
    $scan = consistency_scan($pdo, $run, $cases);
    content_check($scan['missing'] === [], 'Final scan before harness cleanup: no surviving record references a missing uploaded file');
    content_check($scan['orphan_files'] === [], 'Final scan before harness cleanup: no unreferenced application upload/thumbnail files');
    $ledger['before_harness_cleanup'] = $scan;
    $danglingActors = (int) $pdo->query('SELECT COUNT(*) FROM admin_activity_logs l LEFT JOIN admins a ON a.id=l.admin_id WHERE l.admin_id IS NOT NULL AND a.id IS NULL')->fetchColumn();
    content_check($danglingActors === 0, 'Live audit log actor references resolve to existing test administrators');
    $ledger['dangling_log_actors'] = $danglingActors;
    $ledger['note'] = 'Measured before test harness deletes synthetic guard rows. Preserved snapshots after harness cleanup are evidence, not production orphan counts. Deleted entity audit-log references are intentional history, not broken live relations.';
    file_put_contents($run['snapshot'] . '/consistency-ledger.json', json_encode($ledger, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
