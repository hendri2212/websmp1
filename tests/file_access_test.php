<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/../includes/functions.php';

$temporaryRoot = sys_get_temp_dir() . '/school-file-access-' . bin2hex(random_bytes(8));
$allowedRoot = $temporaryRoot . '/ppdb';
$siblingRoot = $temporaryRoot . '/ppdb-archive';
$checks = 0;
$exitCode = 0;
function file_access_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
    echo '[OK] ' . $label . PHP_EOL;
}

try {
    mkdir($allowedRoot . '/nested', 0775, true);
    mkdir($siblingRoot, 0775, true);
    file_put_contents($allowedRoot . '/valid.pdf', 'test fixture');
    file_put_contents($allowedRoot . '/nested/valid.pdf', 'test fixture');
    file_put_contents($siblingRoot . '/private.pdf', 'test fixture');

    file_access_assert(file_is_within_directory($allowedRoot . '/valid.pdf', $allowedRoot), 'Normal allowed file accepted');
    file_access_assert(file_is_within_directory($allowedRoot . '/nested/valid.pdf', $allowedRoot), 'Nested allowed file accepted');
    file_access_assert(file_is_within_directory($allowedRoot . '/valid.pdf', $allowedRoot . '/'), 'Trailing separator accepted');
    file_access_assert(!file_is_within_directory($siblingRoot . '/private.pdf', $allowedRoot), 'Sibling sharing prefix denied');
    file_access_assert(!file_is_within_directory($allowedRoot . '/../ppdb-archive/private.pdf', $allowedRoot), 'Parent traversal to sibling denied');
    file_access_assert(!file_is_within_directory(__FILE__, $allowedRoot), 'Unrelated existing file denied');
    file_access_assert(!file_is_within_directory($allowedRoot . '/missing.pdf', $allowedRoot), 'Missing file denied');
    file_access_assert(!file_is_within_directory($allowedRoot, $allowedRoot), 'Directory is not a downloadable file');
    file_access_assert(!file_is_within_directory($allowedRoot . '/valid.pdf', $temporaryRoot . '/missing'), 'Missing root denied');
    file_access_assert(!file_is_within_directory($allowedRoot . '/valid.pdf', $allowedRoot . '/valid.pdf'), 'File cannot act as storage root');
    if (PHP_OS_FAMILY === 'Windows') {
        file_access_assert(file_is_within_directory(strtoupper($allowedRoot . '/valid.pdf'), strtolower($allowedRoot)), 'Windows path casing accepted');
    }
    echo 'Passed: ' . $checks . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    foreach ([$allowedRoot . '/valid.pdf', $allowedRoot . '/nested/valid.pdf', $siblingRoot . '/private.pdf'] as $fixture) {
        if (is_file($fixture)) {
            unlink($fixture);
        }
    }
    foreach ([$allowedRoot . '/nested', $allowedRoot, $siblingRoot, $temporaryRoot] as $directory) {
        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
}
exit($exitCode);
