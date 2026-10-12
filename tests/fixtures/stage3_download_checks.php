<?php

declare(strict_types=1);

function stage3_download_checks(PDO $pdo, array $run, array $anonymous, array &$ownedSettings): void
{
    $root = $run['snapshot'];
    foreach (['storage/uploads/documents/nested', 'storage/uploads/accreditation',
        'storage/uploads/documents-private', 'storage/uploads/accreditation-private', 'storage/uploads/ppdb', 'test-private'] as $folder) {
        mkdir($root . '/' . $folder, 0700, true);
    }
    $pdf = file_get_contents($root . '/fixture.pdf');
    $sentinel = 'OUTSIDE_ALLOWED_DIRECTORY_' . $run['tag'];
    $files = [
        'storage/uploads/documents/valid.pdf'=>$pdf,
        'storage/uploads/documents/nested/valid.pdf'=>$pdf,
        'storage/uploads/accreditation/valid.pdf'=>$pdf,
        'storage/uploads/documents-private/outside.pdf'=>$sentinel,
        'storage/uploads/accreditation-private/outside.pdf'=>$sentinel,
        'test-private/outside.pdf'=>$sentinel,
        'storage/uploads/ppdb/private.pdf'=>$sentinel,
    ];
    foreach ($files as $path=>$bytes) {
        file_put_contents($root . '/' . $path, $bytes);
    }
    $protected = ['config/database.php','config/app.php','templates/header.php'];
    $baseline = [];
    foreach (array_merge(array_keys($files), $protected) as $path) {
        $baseline[$path] = hash_file('sha256', $root . '/' . $path);
    }
    $insert = $pdo->prepare('INSERT INTO school_documents (title,category,description,file_path,original_filename,file_size,is_active) VALUES (:title,:category,:description,:path,:filename,:size,:active)');
    $seed = static function (string $label, string $path, int $active = 1, string $filename = 'valid.pdf') use ($pdo, $insert, $run, $pdf): int {
        $insert->execute(['title'=>'TEST_TAHAP3_DOWNLOAD_' . $run['tag'] . '_' . $label,
            'category'=>'Synthetic', 'description'=>'Synthetic download test', 'path'=>$path,
            'filename'=>$filename, 'size'=>strlen($pdf), 'active'=>$active]);
        return (int) $pdo->lastInsertId();
    };
    $counter = static function (int $id) use ($pdo): int {
        $stmt = $pdo->prepare('SELECT download_count FROM school_documents WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    };
    $valid = $seed('VALID', 'storage/uploads/documents/valid.pdf');
    $nested = $seed('NESTED', 'storage/uploads/documents/nested/valid.pdf');
    foreach ([$valid, $nested] as $id) {
        $before = $counter($id);
        $reply = content_http($anonymous, '/dokumen/unduh?id=' . $id);
        content_check($reply['status'] === 200 && hash('sha256', $reply['body']) === hash('sha256', $pdf), 'Document ' . $id . ': public download returns exact valid PDF bytes');
        content_check(($reply['headers']['content-type'] ?? '') === 'application/pdf'
            && (int) ($reply['headers']['content-length'] ?? 0) === strlen($pdf)
            && str_starts_with($reply['headers']['content-disposition'] ?? '', 'attachment;'), 'Document ' . $id . ': download headers correct');
        content_check($counter($id) === $before + 1, 'Document ' . $id . ': successful download increments only its counter');
    }
    $denied = [
        'MISSING_FILE'=>'storage/uploads/documents/missing.pdf',
        'DIRECTORY'=>'storage/uploads/documents/nested',
        'SIBLING_PREFIX'=>'storage/uploads/documents-private/outside.pdf',
        'TRAVERSAL_SIBLING'=>'storage/uploads/documents/../documents-private/outside.pdf',
        'TRAVERSAL_PRIVATE'=>'storage/uploads/documents/../../../test-private/outside.pdf',
        'BACKSLASH_TRAVERSAL'=>'storage\\uploads\\documents\\..\\documents-private\\outside.pdf',
        'PPDB_PRIVATE'=>'storage/uploads/ppdb/private.pdf',
        'PPDB_TRAVERSAL'=>'storage/uploads/documents/../ppdb/private.pdf',
    ];
    foreach ($denied as $label=>$path) {
        $id = $seed($label, $path);
        $reply = content_http($anonymous, '/dokumen/unduh?id=' . $id);
        content_check($reply['status'] === 404 && $reply['body'] === 'Dokumen tidak ditemukan.', 'Document ' . $label . ': rejects unavailable/outside path without details');
        content_check($counter($id) === 0 && !str_contains($reply['body'], $sentinel), 'Document ' . $label . ': no outside bytes or counter mutation');
    }
    $inactive = $seed('INACTIVE', 'storage/uploads/documents/valid.pdf', 0);
    foreach (['0','-1','invalid','999999999','../test-private/outside.pdf',(string) $inactive] as $id) {
        $reply = content_http($anonymous, '/dokumen/unduh?id=' . rawurlencode($id));
        content_check($reply['status'] === 404 && $reply['body'] === 'Dokumen tidak ditemukan.', 'Document ID ' . $id . ': invalid/unknown/inactive denied');
    }
    content_check($counter($inactive) === 0, 'Inactive document download count unchanged');
    foreach ([$valid . 'abc', $valid . '.5'] as $id) {
        $before = $counter($valid);
        $reply = content_http($anonymous, '/dokumen/unduh?id=' . rawurlencode($id));
        content_check($reply['status'] === 404 && $counter($valid) === $before, 'Malformed numeric ID ' . $id . ': must not resolve to a valid document or increment counter');
    }
    foreach (['file','filename','path'] as $parameter) {
        foreach (['../test-private/outside.pdf','storage/uploads/documents-private/outside.pdf'] as $value) {
            $reply = content_http($anonymous, '/dokumen/unduh?id=' . $valid . '&' . http_build_query([$parameter=>$value]));
            content_check($reply['status'] === 200 && $reply['body'] === $pdf && !str_contains($reply['body'], $sentinel), 'Document request ' . $parameter . '=' . $value . ': cannot override stored path');
        }
    }
    $unsafeName = '../../odd"' . "\r\nX-Download-Injected: yes.pdf";
    $named = $seed('HEADER', 'storage/uploads/documents/valid.pdf', 1, $unsafeName);
    $reply = content_http($anonymous, '/dokumen/unduh?id=' . $named);
    $disposition = $reply['headers']['content-disposition'] ?? '';
    content_check($reply['status'] === 200 && !isset($reply['headers']['x-download-injected'])
        && !str_contains($disposition, '/') && !str_contains($disposition, '\\')
        && substr_count($disposition, '"') === 2, 'Document metadata filename: path/quotes/CRLF cannot inject response headers');

    $setting = $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?)');
    $setting->execute(['schoolAccreditation', '{}']);
    $ownedSettings[] = ['id'=>(int) $pdo->lastInsertId(), 'setting_key'=>'schoolAccreditation'];
    $update = $pdo->prepare('UPDATE site_settings SET setting_value = ? WHERE id = ? AND setting_key = ?');
    $set = static function (string $path, string $filename = 'valid.pdf') use ($update, &$ownedSettings): void {
        $update->execute([json_encode(['filePath'=>$path,'originalFilename'=>$filename], JSON_THROW_ON_ERROR), $ownedSettings[0]['id'], 'schoolAccreditation']);
    };
    $set('storage/uploads/accreditation/valid.pdf', $unsafeName);
    foreach (['', '?filename=..%2Ftest-private%2Foutside.pdf', '?path=..%2Ftest-private%2Foutside.pdf', '?file=..%2Ftest-private%2Foutside.pdf'] as $query) {
        $reply = content_http($anonymous, '/akreditasi/file' . $query);
        content_check($reply['status'] === 200 && $reply['body'] === $pdf, 'Accreditation ' . ($query ?: 'valid file') . ': returns only configured PDF');
        content_check(($reply['headers']['content-type'] ?? '') === 'application/pdf'
            && str_starts_with($reply['headers']['content-disposition'] ?? '', 'inline;')
            && ($reply['headers']['x-content-type-options'] ?? '') === 'nosniff'
            && !isset($reply['headers']['x-download-injected']), 'Accreditation: safe inline PDF headers');
    }
    foreach (['', 'storage/uploads/accreditation/missing.pdf', 'storage/uploads/accreditation',
        'storage/uploads/accreditation-private/outside.pdf',
        'storage/uploads/accreditation/../accreditation-private/outside.pdf',
        'storage/uploads/accreditation/../../../test-private/outside.pdf',
        'storage\\uploads\\accreditation\\..\\accreditation-private\\outside.pdf',
        'storage/uploads/ppdb/private.pdf', 'storage/uploads/accreditation/../ppdb/private.pdf'] as $path) {
        $set($path);
        $reply = content_http($anonymous, '/akreditasi/file');
        content_check($reply['status'] === 404 && in_array($reply['body'], ['Lembar akreditasi belum tersedia.','Lembar akreditasi tidak ditemukan.'], true)
            && !str_contains($reply['body'], $sentinel), 'Accreditation path ' . ($path ?: '(empty)') . ': missing/non-file/outside denied generically');
    }
    foreach ($baseline as $path=>$hash) {
        content_check(hash_file('sha256', $root . '/' . $path) === $hash, 'Download never modifies fixture/protected file: ' . $path);
    }
}
