<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require $argv[1];
$pdo = stage3_test_database();
if (isset($argv[2])) {
    $connection = json_decode(file_get_contents(dirname(__DIR__, 2) . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
    $zip = new ZipArchive();
    if ($zip->open($argv[2]) !== true) { exit(1); }
    $safe = true;
    for ($i=0; $i<$zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i); $body = $zip->getFromIndex($i);
        $safe = $safe && !str_contains($name, '..') && !str_starts_with($name, '/')
            && !str_starts_with($name, 'config/') && !str_contains($name, '.env')
            && !str_contains($body, $connection['password']) && !str_contains($body, $connection['username']);
    }
    $results = [
        ['passed'=>$safe,'label'=>'ZIP entries exclude path traversal and DB connection credentials/config'],
        ['passed'=>$zip->locateName('manifest.json') !== false && $zip->locateName('database/backup-website_sekolah_test_stage3.sql') !== false,'label'=>'ZIP contains manifest and actual schema/data SQL'],
    ];
    $zip->close(); echo json_encode($results, JSON_THROW_ON_ERROR); exit;
}
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/error_handling.php';
configure_web_error_handling();

// Extract only function definitions: never bypass or execute the real endpoint's authorization flow.
$tokens = token_get_all(file_get_contents(__DIR__ . '/db_backup.php'));
$definitions = '';
for ($i=0; $i<count($tokens); $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) { continue; }
    $depth = 0; $started = false;
    do {
        $token = $tokens[$i];
        $definitions .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $started = true; }
        if ($token === '}') { $depth--; }
        $i++;
    } while ($i<count($tokens) && (!$started || $depth>0));
    $i--; $definitions .= "\n";
}
eval($definitions);
$results = [];
function backup_probe_check(bool $passed, string $label): void
{
    global $results;
    $results[] = ['passed'=>$passed,'label'=>$label];
}
$archives = [];
$fixture = dirname(__DIR__) . '/public/uploads/TEST_TAHAP5_BACKUP.txt';
file_put_contents($fixture, 'SAFE_SYNTHETIC_UPLOAD');
try {
    foreach (['../bad.zip', '../../config/database.php', 'backup.zip', "bad\0.zip", 'backup-lengkap-test-20261009-000000.zip/../x'] as $bad) {
        $result = build_full_backup_zip($pdo, 'website_sekolah_test_stage3', [], $bad);
        backup_probe_check($result['path'] === null && $result['error'] === 'Nama backup tidak valid.', 'Malformed/traversal name rejected before file creation');
    }
    $filename = 'backup-lengkap-test-' . date('Ymd-His') . '.zip';
    for ($n=0; $n<2; $n++) {
        $result = build_full_backup_zip($pdo, 'website_sekolah_test_stage3', [], $filename);
        $archives[] = $result['path'];
        backup_probe_check(is_file($result['path']) && backup_path_within($result['path'], dirname(__DIR__) . '/storage/backups/.temporary'), 'Archive stored in verified private backup directory');
    }
    backup_probe_check($archives[0] !== $archives[1], 'Same download name gets distinct reserved temporary paths');
    $zip = new ZipArchive(); $zip->open($archives[0]);
    backup_probe_check($zip->getFromName('files/public/uploads/TEST_TAHAP5_BACKUP.txt') === 'SAFE_SYNTHETIC_UPLOAD', 'Valid owned upload included without path manipulation');
    backup_probe_check($zip->locateName('config/database.php') === false && $zip->locateName('.env') === false, 'Connection configuration excluded');
    $zip->close();
    $secretFixture = dirname(__DIR__) . '/public/uploads/.env';
    file_put_contents($secretFixture, 'SAFE_SYNTHETIC_CREDENTIAL_SENTINEL');
    try {
        build_full_backup_zip($pdo, 'testing', [], $filename);
        backup_probe_check(false, 'Misplaced operational configuration causes backup to fail closed');
    } catch (RuntimeException $exception) {
        backup_probe_check($exception->getMessage() === 'Backup gagal dibuat. Silakan coba lagi.', 'Misplaced operational configuration causes backup to fail closed');
    } finally { unlink($secretFixture); }
    try {
        backup_relative_path(dirname(__DIR__, 2) . '/connection.json', dirname(__DIR__));
        backup_probe_check(false, 'Outside-root file rejected');
    } catch (RuntimeException $exception) { backup_probe_check(true, 'Outside-root file rejected'); }
    $failure = new class extends PDO {
        public function __construct() {}
        public function query(string $query, ?int $fetchMode=null, mixed ...$args): PDOStatement|false {
            throw new PDOException('SENTINEL_PRIVATE_PASSWORD mysql:host=PRIVATE SELECT SECRET');
        }
    };
    $before = glob(dirname(__DIR__) . '/storage/backups/.temporary/*.zip') ?: [];
    try {
        build_full_backup_zip($failure, 'testing', [], $filename);
        backup_probe_check(false, 'Backup SQL failure rejected');
    } catch (RuntimeException $exception) {
        backup_probe_check($exception->getMessage() === 'Backup gagal dibuat. Silakan coba lagi.', 'Backup failure exposes only generic message');
    }
    backup_probe_check((glob(dirname(__DIR__) . '/storage/backups/.temporary/*.zip') ?: []) === $before, 'Failed backup leaves no new temporary archive');
} finally {
    foreach ($archives as $archive) { if (is_file($archive)) { unlink($archive); } }
    if (is_file($fixture)) { unlink($fixture); }
}
echo json_encode($results, JSON_THROW_ON_ERROR);
