<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

$root = dirname(__DIR__);
$directory = null;
$process = null;
$exitCode = 0;
$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException('Failed assertion: ' . $label); }
};
$write = static function (string $path, string $bytes): void {
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true)) { throw new RuntimeException('Cannot create fixture directory'); }
    if (file_put_contents($path, $bytes) === false) { throw new RuntimeException('Cannot write fixture'); }
};
try {
    $parent = $root . '/storage/testing';
    $assert(is_dir($parent) && !is_link($parent) && !is_link($root . '/storage'), 'private testing parent');
    $directory = $parent . '/stage6-cli-guard-' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    $entries = ['database/seed_final_content.php', 'database/maintenance_legacy_schema.php', 'database/initialize_admin.php'];
    foreach (glob($root . '/tests/*.php') as $file) { $entries[] = 'tests/' . basename($file); }
    foreach ($entries as $entry) { $write($directory . '/' . $entry, file_get_contents($root . '/' . $entry)); }
    // No real bootstrap/config is copied: reaching this canary is a test failure.
    $write($directory . '/includes/bootstrap.php', '<?php file_put_contents(__DIR__ . "/../BOOTSTRAP_RAN", "BOOTSTRAP_EXECUTED"); echo "BOOTSTRAP_EXECUTED";');
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    $assert(is_resource($socket), 'loopback port allocated');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-d', 'log_errors=1', '-d', 'error_log=' . $directory . '/php.log',
        '-S', '127.0.0.1:' . $port, '-t', $directory],
        [0 => ['pipe', 'r'], 1 => ['file', $directory . '/stdout.log', 'a'], 2 => ['file', $directory . '/stderr.log', 'a']], $pipes, $root);
    $assert(is_resource($process), 'isolated server started');
    fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
        if ($socket) { fclose($socket); $ready = true; break; }
        usleep(100000);
    }
    $assert($ready, 'server ready');
    foreach ($entries as $entry) {
        foreach (['GET', 'POST'] as $method) {
            $curl = curl_init('http://127.0.0.1:' . $port . '/' . $entry);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 5]);
            if ($method === 'POST') { curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => 'mode=apply&confirm=1']); }
            $raw = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            $assert(is_string($raw) && $status === 403, $method . ' CLI-only entry rejected: ' . $entry);
            $assert(!str_contains($raw, 'BOOTSTRAP_EXECUTED') && !str_contains($raw, '<?php'), 'no bootstrap or source response');
            $assert(!file_exists($directory . '/BOOTSTRAP_RAN'), 'no bootstrap side effect');
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($process)) {
        proc_terminate($process); proc_close($process);
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.2);
        $checks++;
        if ($socket) { fclose($socket); fwrite(STDERR, 'Owned test listener still active.' . PHP_EOL); $exitCode = 1; }
    }
    if ($directory && is_dir($directory) && !is_link($directory)
        && str_starts_with(realpath($directory), realpath($root . '/storage/testing') . DIRECTORY_SEPARATOR)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
        }
        rmdir($directory);
    }
}
if ($exitCode === 0) { echo 'PASS: ' . $checks . ' CLI guard checks across ' . count($entries) . ' entries; no bootstrap/database/migration executed.' . PHP_EOL; }
exit($exitCode);
