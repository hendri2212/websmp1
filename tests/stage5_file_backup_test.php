<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (($argv[1] ?? '') !== '--run') { fwrite(STDERR, "Use --run <backup snapshot>.\n"); exit(1); }
require __DIR__ . '/stage3_database_preflight.php';

$checks = [];
$created = [];
$process = null;
$root = null;
function file_backup_check(bool $passed, string $label): void
{
    global $checks;
    $checks[] = compact('passed','label');
    echo ($passed ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$passed) { throw new RuntimeException('File backup verification failed.'); }
}

// Test-only controlled recovery: never use extractTo on an unverified archive.
function file_backup_recover(string $archive, array $expected, string $destination): array
{
    $zip = new ZipArchive();
    if ($zip->open($archive) !== true) { throw new RuntimeException('Unsafe archive.'); }
    $files = [];
    try {
        $metadata = ['manifest.json','database/schema.sql','database/backup-website_sekolah_test_stage3.sql'];
        $seen = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (isset($seen[$name])) { throw new RuntimeException('Unsafe archive.'); }
            $seen[$name] = true;
            if (in_array($name, $metadata, true)) { continue; }
            if (!preg_match('~\Afiles/(public|storage)/uploads/[a-zA-Z0-9_-]+/(?:thumbs/)?[a-zA-Z0-9_-]+\.(webp|png|jpg|jpeg|pdf)\z~', $name)
                || !isset($expected[$name])) { throw new RuntimeException('Unsafe archive.'); }
            if (!$zip->getExternalAttributesIndex($i, $system, $attributes)) { throw new RuntimeException('Unsafe archive.'); }
            if ($system === ZipArchive::OPSYS_UNIX) {
                $mode = ($attributes >> 16) & 0xffff;
                if (($mode & 0170000) === 0120000 || ($mode & 0111) !== 0) { throw new RuntimeException('Unsafe archive.'); }
            }
            $bytes = $zip->getFromIndex($i);
            if (!is_string($bytes) || hash('sha256', $bytes) !== $expected[$name]) { throw new RuntimeException('Unsafe archive.'); }
            $files[$name] = $bytes;
        }
        if (array_diff(array_keys($expected), array_keys($files))) { throw new RuntimeException('Incomplete archive.'); }
        // Validate the entire archive before creating any directory or file.
        if (file_exists($destination) || !mkdir($destination, 0700)) { throw new RuntimeException('Recovery destination unavailable.'); }
        $paths = [];
        foreach ($files as $name => $bytes) {
            $path = $destination . '/' . substr($name, 6);
            if (!is_dir(dirname($path))) { mkdir(dirname($path), 0700, true); }
            $handle = fopen($path, 'x+b');
            if (!$handle) { throw new RuntimeException('Recovery failed.'); }
            $written = fwrite($handle, $bytes); fclose($handle);
            if ($written !== strlen($bytes) || !chmod($path, 0600)) { throw new RuntimeException('Recovery failed.'); }
            $paths[$path] = hash('sha256', $bytes);
        }
        return $paths;
    } finally { $zip->close(); }
}

try {
    stage3_test_database(); // Read-only preflight only; this test performs no database writes.
    $private = realpath(dirname(__DIR__) . '/storage/testing/stage3-mariadb');
    $snapshot = realpath($argv[2] ?? '');
    file_backup_check(is_string($snapshot) && dirname($snapshot) === $private
        && preg_match('/\Ahttp-crud3_[a-f0-9]{12}\z/', basename($snapshot)) === 1, 'Verified private backup snapshot');
    $ledger = json_decode(file_get_contents($snapshot . '/backup-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
    $expected = json_decode(file_get_contents($snapshot . '/backup-completeness-full-expected.json'), true, 512, JSON_THROW_ON_ERROR);
    $archive = $snapshot . '/backup-completeness-full.zip';
    file_backup_check(hash_file('sha256', $archive) === $ledger['full']['sha256'], 'Testing application ZIP hash matches recorded backup');
    $root = $private . '/file-backup5-' . bin2hex(random_bytes(6)); mkdir($root, 0700);
    $created = file_backup_recover($archive, $expected['files'], $root . '/recovered');
    file_backup_check(count($created) === 14, 'All 14 dummy uploads/thumbnails recovered from application ZIP');
    foreach ($created as $path => $hash) {
        file_backup_check(hash_file('sha256', $path) === $hash, 'Recovered file bytes match: ' . basename($path));
        file_backup_check(!is_link($path) && str_starts_with(realpath($path), realpath($root . '/recovered') . DIRECTORY_SEPARATOR), 'Recovered file contained and not symlink');
        if (PHP_OS_FAMILY !== 'Windows') {
            file_backup_check((fileperms($path) & 0111) === 0, 'Recovered file has no POSIX execute bits');
        }
    }
    $original = new ZipArchive(); $original->open($archive);
    $cases = ['../outside.txt','files/public/uploads/news/../../outside.jpg','files/public/uploads/news/..\\outside.jpg',
        '/absolute.jpg','C:/outside.jpg','files/public/uploads/news/image.php.jpg',
        'files/public/uploads/news/payload.phtml','files/public/uploads/news/duplicate..jpg',
        'files/public/uploads/news/symlink.jpg','files/public/uploads/news/executable.jpg'];
    foreach ($cases as $number => $bad) {
        $fixture = $root . '/invalid-' . $number . '.zip';
        $zip = new ZipArchive(); $zip->open($fixture, ZipArchive::CREATE | ZipArchive::EXCL);
        for ($i = 0; $i < $original->numFiles; $i++) { $zip->addFromString($original->getNameIndex($i), $original->getFromIndex($i)); }
        $bytes = 'SAFE_DUMMY_NOT_A_SCRIPT'; $zip->addFromString($bad, $bytes);
        if ($number === 8 || $number === 9) {
            $mode = $number === 8 ? 0120777 : 0100755;
            $zip->setExternalAttributesName($bad, ZipArchive::OPSYS_UNIX, $mode << 16);
        }
        $zip->close();
        $unsafeExpected = $expected['files'] + [$bad=>hash('sha256', $bytes)];
        $destination = $root . '/rejected-' . $number;
        $denied = false;
        try { file_backup_recover($fixture, $unsafeExpected, $destination); }
        catch (RuntimeException $exception) { $denied = true; }
        file_backup_check($denied && !file_exists($destination), 'Unsafe ZIP rejected before any extraction: case ' . $number);
        unlink($fixture);
    }
    $original->close();
    file_backup_check(!file_exists($root . '/outside.txt'), 'Traversal never creates outside canary file');
    // Execute protection is tested using the unchanged application preview router, not the CLI OS executable flag.
    $project = dirname(__DIR__);
    mkdir($root . '/recovered/includes');
    foreach (glob($project . '/includes/*.php') as $source) { copy($source, $root . '/recovered/includes/' . basename($source)); }
    copy($project . '/preview_router.php', $root . '/recovered/preview_router.php');
    $script = $root . '/recovered/public/uploads/news/SAFE_CONTROL.php';
    $control = '<?php echo "SAFE_EXECUTION_CANARY";'; file_put_contents($script, $control); $created[$script] = hash('sha256', $control);
    $socket = @stream_socket_client('tcp://127.0.0.1:8091', $code, $message, 0.2);
    if (is_resource($socket)) { fclose($socket); throw new RuntimeException('Test port occupied.'); }
    $process = proc_open([PHP_BINARY, '-S','127.0.0.1:8091','-t',$root . '/recovered',$root . '/recovered/preview_router.php'],
        [0=>['pipe','r'],1=>['file',$root . '/http.out.log','a'],2=>['file',$root . '/http.err.log','a']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start isolated file server.'); } fclose($pipes[0]);
    for ($i=0; $i<30; $i++) { $socket = @stream_socket_client('tcp://127.0.0.1:8091', $code, $message, 0.1); if (is_resource($socket)) { fclose($socket); break; } usleep(100000); }
    $requests = ['/public/uploads/news/SAFE_CONTROL.php'=>404, '/storage/uploads/documents/file.pdf'=>403,
        '/public/uploads/news/../../../../connection.json'=>403];
    foreach ($requests as $url => $status) {
        $curl = curl_init('http://127.0.0.1:8091' . $url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_PATH_AS_IS=>true,CURLOPT_TIMEOUT=>5]);
        $body = curl_exec($curl); $actual = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        file_backup_check($actual === $status && is_string($body) && !str_contains($body, 'SAFE_EXECUTION_CANARY'), 'Preview HTTP denies executable/private/traversal request: ' . $url);
    }
    $image = array_key_first($expected['files']);
    $curl = curl_init('http://127.0.0.1:8091/' . substr($image, 6)); curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>5]);
    $bytes = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    file_backup_check($status === 200 && is_string($bytes) && hash('sha256', $bytes) === $expected['files'][$image], 'Restored image served as exact static bytes');
} catch (Throwable $exception) {
    $checks[] = ['passed'=>false,'label'=>'File backup test stopped safely (' . get_class($exception) . ').'];
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach ($created as $path => $hash) { file_backup_check(!is_link($path) && hash_file('sha256', $path) === $hash && unlink($path), 'Owned recovered dummy/control file cleaned'); }
    $summary = ['date'=>gmdate('c'),'platform'=>PHP_OS_FAMILY,'posix_execute_bit_test'=>PHP_OS_FAMILY === 'Windows' ? 'Not applicable; NTFS/production permissions not proven' : 'Tested',
        'passed'=>count(array_filter($checks, static fn($check)=>$check['passed'])), 'checks'=>$checks];
    if ($root !== null) { file_put_contents($root . '/result.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)); }
    echo 'RESULT: ' . $summary['passed'] . '/' . count($checks) . PHP_EOL;
    if ($root !== null) { echo 'Private evidence: ' . $root . PHP_EOL; }
}
exit(count($checks) === $summary['passed'] ? 0 : 1);
