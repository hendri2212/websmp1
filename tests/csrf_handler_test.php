<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/../includes/functions.php';
final class CsrfTestOperation extends RuntimeException {}

function db_available(): bool { return true; }

// Extract the actual POST block, preserving its conditions and branches.
function csrf_post_block(string $source): ?string
{
    $tokens = token_get_all($source);
    $offsets = [];
    $offset = 0;
    foreach ($tokens as $token) {
        $offsets[] = $offset;
        $offset += strlen(is_array($token) ? $token[1] : $token);
    }
    foreach ($tokens as $start => $token) {
        if (!is_array($token) || $token[0] !== T_IF) {
            continue;
        }
        $cursor = $start + 1;
        while (isset($tokens[$cursor]) && is_array($tokens[$cursor]) && $tokens[$cursor][0] === T_WHITESPACE) {
            $cursor++;
        }
        if (($tokens[$cursor] ?? null) !== '(') {
            continue;
        }
        $depth = 0;
        $condition = '';
        do {
            $part = $tokens[$cursor++];
            $condition .= is_array($part) ? $part[1] : $part;
            if ($part === '(') { $depth++; }
            if ($part === ')') { $depth--; }
        } while ($depth > 0 && isset($tokens[$cursor]));
        if (!str_contains($condition, 'REQUEST_METHOD') || !str_contains($condition, "'POST'")) {
            continue;
        }
        while (isset($tokens[$cursor]) && is_array($tokens[$cursor]) && $tokens[$cursor][0] === T_WHITESPACE) {
            $cursor++;
        }
        if (($tokens[$cursor] ?? null) !== '{') {
            throw new RuntimeException('Unsupported POST block');
        }
        $depth = 0;
        do {
            $part = $tokens[$cursor++];
            if ($part === '{') { $depth++; }
            if ($part === '}') { $depth--; }
        } while ($depth > 0 && isset($tokens[$cursor]));
        $end = $offsets[$cursor - 1] + 1;
        return substr($source, $offsets[$start], $end - $offsets[$start]);
    }
    return null;
}

function csrf_instrument_operations(string $block): string
{
    $result = '';
    foreach (token_get_all('<?php ' . $block) as $token) {
        if (is_array($token) && $token[0] === T_OPEN_TAG) {
            continue;
        }
        if (is_array($token) && $token[0] === T_STRING
            && preg_match('/^(save_|delete_|update_|uploaded_|build_.*backup|admin_login$|admin_logout$|admin_username$|admin_.*_row$)/', $token[1])) {
            $name = $token[1];
            $probe = 'csrf_probe_' . $name;
            if (!function_exists($probe)) {
                if ($name === 'admin_username') {
                    eval('function ' . $probe . '(...$args) { return "csrf-test"; }');
                } elseif (str_starts_with($name, 'admin_') && str_ends_with($name, '_row')) {
                    eval('function ' . $probe . '(...$args) { return ["image_path" => "/public/logo-sekolah.png", "file_path" => "storage/uploads/documents/test.pdf", "original_filename" => "test.pdf", "file_size" => 100]; }');
                } else {
                    eval('function ' . $probe . '(...$args) { $GLOBALS["csrf_reached_operation"] = "' . $name . '"; throw new CsrfTestOperation("' . $name . '"); }');
                }
            }
            $result .= $probe;
        } else {
            $result .= is_array($token) ? $token[1] : $token;
        }
    }
    return $result;
}

function csrf_run_handler(string $block, array $payload, string $method, array $query = []): ?string
{
    $_POST = $payload;
    $_GET = $query;
    $_FILES = [];
    $_SERVER['REQUEST_METHOD'] = $method;
    $GLOBALS['csrf_reached_operation'] = null;
    $error = $message = $ppdbError = $ppdbMessage = $ppdbReceiptUrl = '';
    $currentAcademicYear = '2026/2027';
    $galleryImageMaxBytes = IMAGE_UPLOAD_MAX_BYTES;
    $content = require __DIR__ . '/../config/site_data.php';
    $config = ['database' => 'csrf_test'];
    $pdo = new stdClass();
    $tableSummaries = [];
    $genderOptions = ['Laki-laki', 'Perempuan'];
    $registrationPathOptions = ['Zonasi', 'Prestasi', 'Afirmasi', 'Mutasi'];
    try {
        eval($block);
    } catch (CsrfTestOperation $operation) {
        return $operation->getMessage();
    }
    return $GLOBALS['csrf_reached_operation'];
}

$checks = 0;
function csrf_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException($label); }
    $checks++;
}

set_error_handler(static function (int $severity, string $message): never { throw new RuntimeException($message); });
try {
    $_SESSION = [];
    $validToken = csrf_token();
    csrf_assert(strlen($validToken) === 64 && csrf_token_is_valid($validToken), 'Generated token must validate');
    foreach ([null, '', 'wrong', [], ['token'], 1, true] as $invalid) {
        csrf_assert(!csrf_token_is_valid($invalid), 'Malformed token accepted');
    }
    $_SESSION['csrf_token'] = [];
    csrf_assert(!csrf_token_is_valid($validToken), 'Malformed session token accepted');
    $_SESSION['csrf_token'] = '';
    csrf_assert(!csrf_token_is_valid(''), 'Empty token accepted');
    $_SESSION['csrf_token'] = $validToken;

    $payload = [
        'id' => '', 'title' => 'CSRF test', 'description' => 'CSRF test', 'content' => 'CSRF test',
        'category' => 'Test', 'location' => 'Test', 'starts_at' => '2026-10-06T08:00', 'ends_at' => '',
        'sort_order' => '0', 'name' => 'CSRF test', 'full_name' => 'CSRF test', 'username' => 'csrf-test',
        'role' => 'editor', 'password' => 'Test-password-123', 'current_password' => 'Test-password-123',
        'new_password' => 'New-password-123', 'confirm_password' => 'New-password-123',
        'question' => 'CSRF test', 'answer' => 'CSRF test', 'slug' => 'csrf-test', 'excerpt' => 'Test',
        'published_at' => '2026-10-06T08:00', 'is_active' => '1', 'section' => 'informasi',
        'level' => 'Test', 'achiever_name' => 'Test', 'achievement_date' => '2026-10-06',
        'condition_status' => 'Baik', 'message_id' => '1', 'application_id' => '1', 'status' => 'baru',
        'admin_notes' => 'Test', 'student_name' => 'Test', 'birth_place' => 'Test', 'birth_date' => '2020-01-01',
        'gender' => 'Laki-laki', 'previous_school' => 'Test', 'registration_path' => 'Zonasi',
        'parent_name' => 'Test', 'parent_phone' => '123', 'parent_email' => 'test@example.com',
        'address' => 'Test', 'privacy_consent' => '1', 'email' => 'test@example.com', 'phone' => '123',
        'subject' => 'Test', 'position' => 'Test', 'message' => 'Test', 'backup_type' => 'sql',
    ];
    $adminOnly = in_array('--admin-only', $argv, true);
    $paths = $adminOnly
        ? array_values(array_filter(glob(__DIR__ . '/../admin/*.php'), static fn(string $path): bool => !str_starts_with(basename($path), 'ppdb')))
        : array_merge(glob(__DIR__ . '/../admin/*.php'), [__DIR__ . '/../pages/ppdb.php', __DIR__ . '/../pages/kontak.php']);
    $handlers = 0;
    $handlerPaths = [];
    $variants = 0;
    foreach ($paths as $path) {
        $block = csrf_post_block(file_get_contents($path));
        if ($block === null) { continue; }
        csrf_assert(str_contains($block, 'csrf_token_is_valid('), 'POST block missing CSRF: ' . basename($path));
        $instrumented = csrf_instrument_operations($block);
        $actions = ['create' => $payload, 'update' => array_replace($payload, ['id' => '1'])];
        if (str_contains($block, "['delete_id']")) {
            $actions['delete'] = array_replace($payload, ['delete_id' => '1']);
        }
        if (str_contains($block, "['status']")) {
            $actions['status'] = $payload;
        }
        if (basename($path) === 'db_backup.php') {
            $actions['backup-zip'] = array_replace($payload, ['backup_type' => 'full']);
        }
        foreach ($actions as $action => $data) {
            foreach (['missing' => null, 'wrong' => 'wrong', 'empty' => '', 'array' => ['token'], 'number' => 1] as $kind => $token) {
                $request = array_replace($data, ['csrf_token' => $token]);
                if ($kind === 'missing') { unset($request['csrf_token']); }
                csrf_assert(csrf_run_handler($instrumented, $request, 'POST') === null, basename($path) . '/' . $action . ': invalid ' . $kind . ' token reached operation');
            }
            $request = array_replace($data, ['csrf_token' => $validToken]);
            csrf_assert(csrf_run_handler($instrumented, $request, 'POST') !== null, basename($path) . '/' . $action . ': valid token did not reach operation');
            csrf_assert(csrf_run_handler($instrumented, $request, 'GET') === null, basename($path) . '/' . $action . ': GET reached operation');
            foreach (['HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'] as $method) {
                csrf_assert(csrf_run_handler($instrumented, $request, $method, $request) === null,
                    basename($path) . '/' . $action . ': ' . $method . ' reached operation');
            }
            $queryOnly = $data;
            unset($queryOnly['csrf_token']);
            csrf_assert(csrf_run_handler($instrumented, $queryOnly, 'POST', ['csrf_token' => $validToken]) === null, basename($path) . '/' . $action . ': query token replaced POST token');
            $_SESSION['csrf_token'] = 'different-session-token';
            csrf_assert(csrf_run_handler($instrumented, $request, 'POST') === null, basename($path) . '/' . $action . ': stale or other-session token reached operation');
            $_SESSION['csrf_token'] = $validToken;
            $variants++;
        }
        $handlers++;
        $handlerPaths[] = basename(dirname($path)) . '/' . basename($path);
        echo '[OK] ' . basename(dirname($path)) . '/' . basename($path) . PHP_EOL;
    }
    // Registration handlers were retired in stage 4; assert exact remaining coverage, not a stale count.
    $expectedHandlers = array_map(static fn(string $name): string => 'admin/' . $name . '.php', [
        'achievements','admins','agenda','announcements','change_password','contact_messages','db_backup',
        'documents','extracurriculars','facilities','faqs','gallery','login','logout','news','settings','staff',
    ]);
    if (!$adminOnly) { $expectedHandlers = array_merge($expectedHandlers, ['admin/ppdb.php','pages/kontak.php']); }
    sort($handlerPaths); sort($expectedHandlers);
    csrf_assert($handlerPaths === $expectedHandlers && $handlers === count($expectedHandlers), 'Unexpected POST handler inventory; review audit coverage');
    $postForms = 0;
    foreach (array_merge($paths, [__DIR__ . '/../templates/admin_header.php']) as $path) {
        $html = '';
        foreach (token_get_all(file_get_contents($path)) as $token) {
            if (is_array($token) && $token[0] === T_INLINE_HTML) {
                $html .= $token[1];
            } elseif (is_array($token) && $token[0] === T_STRING && $token[1] === 'csrf_field') {
                $html .= 'CSRF_TOKEN_FIELD';
            }
        }
        if (!str_contains($html, '<form')) { continue; }
        $document = new DOMDocument();
        $previousXmlErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousXmlErrors);
        foreach ($document->getElementsByTagName('form') as $form) {
            if (strtolower($form->getAttribute('method')) !== 'post') { continue; }
            csrf_assert(str_contains($form->textContent, 'CSRF_TOKEN_FIELD'), 'POST form missing CSRF field: ' . basename($path));
            $postForms++;
        }
    }
    echo 'Scope: ' . ($adminOnly ? 'Admin only, excluding PPDB' : 'All configured handlers') . PHP_EOL;
    echo 'POST handlers: ' . $handlers . PHP_EOL;
    echo 'POST forms with token: ' . $postForms . PHP_EOL;
    echo 'Action variants: ' . $variants . PHP_EOL;
    echo 'Passed checks: ' . $checks . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    restore_error_handler();
}
