<?php

declare(strict_types=1);

function stage4_ppdb_private_file_checks(PDO $pdo, array $run, array $clients, array $anonymous): void
{
    $root = realpath($run['snapshot']);
    $private = realpath(dirname(__DIR__, 2) . '/storage/testing/stage3-mariadb');
    content_require($root && $private && str_starts_with($root, $private . DIRECTORY_SEPARATOR)
        && !is_link($run['snapshot']), 'Private file fixture must stay in verified testing snapshot.');
    $folder = $root . '/storage/uploads/ppdb';
    content_require(!file_exists($folder), 'Do not overwrite any preexisting legacy file directory.');
    mkdir($folder, 0700, true);
    $file = $folder . '/TEST_TAHAP4_PRIVATE_' . $run['tag'] . '.pdf';
    $bytes = 'TEST_TAHAP4_PRIVATE_' . $run['tag'];
    file_put_contents($file, $bytes);
    $baseline = hash_file('sha256', $file);
    $name = basename($file);
    $paths = [
        '/storage/uploads/ppdb/' . $name,
        '/storage/uploads/ppdb/',
        '/%73torage/uploads/ppdb/' . $name,
        '/STORAGE/uploads/ppdb/' . $name,
        '/public/../storage/uploads/ppdb/' . $name,
        '/public/%2e%2e/storage/uploads/ppdb/' . $name,
        '/public/%2e%2e%2fstorage/uploads/ppdb/' . $name,
        '/public/..%5cstorage%5cuploads%5cppdb%5c' . $name,
        '/storage/uploads/ppdb/../ppdb/' . $name,
        '/storage/uploads/ppdb/' . $name . '/extra',
    ];
    $state = content_state($pdo, 'ppdb_applications', $run);
    foreach (['anonymous'=>$anonymous] + $clients as $role=>$client) {
        foreach ($paths as $path) {
            foreach (['GET','POST'] as $method) {
                $reply = content_http($client, $path, null, false, $method);
                content_check(in_array($reply['status'], [400,403,404], true)
                    && !str_contains($reply['body'], $bytes), $role . ': private PPDB bytes denied for ' . $method . ' ' . $path);
            }
        }
        foreach (['../storage/uploads/ppdb/' . $name, '../../config/database.php', $name . "\0", '../123'] as $value) {
            $reply = content_http($client, '/admin/ppdb_download.php?' . http_build_query(['id'=>$value,'file'=>$value,'path'=>$value]));
            content_check($reply['status'] === 410 && !str_contains($reply['body'], $bytes), $role . ': retired download ignores manipulated path/ID');
        }
        content_check(content_state($pdo, 'ppdb_applications', $run) === $state, $role . ': private file probes do not mutate applicant data');
        content_check(is_file($file) && hash_file('sha256', $file) === $baseline, $role . ': ordinary requests do not delete/change retained private file');
    }
    require_once __DIR__ . '/stage3_upload_apache_checks.php';
    stage3_upload_apache_checks($run, [], $paths);
    content_check(is_file($file) && hash_file('sha256', $file) === $baseline, 'Apache requests preserve private PPDB canary');
    file_put_contents($root . '/ppdb-private-file-result.json', json_encode([
        'fixture_relative'=>'storage/uploads/ppdb/' . $name,
        'sha256'=>$baseline, 'preserved_after_requests'=>is_file($file),
        'no_applicant_inserted'=>content_state($pdo, 'ppdb_applications', $run) === $state,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
