<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

function history8_git(array $arguments): string
{
    $process = proc_open(array_merge(['git', '-C', dirname(__DIR__)], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes,
        dirname(__DIR__), null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Read-only Git process unavailable');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_string($output)) {
        throw new RuntimeException('Read-only Git operation failed');
    }
    return $output;
}

try {
    $commits = preg_split('/\s+/', trim(history8_git(['rev-list', '--all'])));
    $rows = [];
    foreach ($commits as $commit) {
        if (!preg_match('/\A[0-9a-f]{40,64}\z/', $commit)) {
            throw new RuntimeException('Invalid history identifier');
        }
        $files = [];
        foreach (['admin/production_check.php', 'database/schema.sql', 'README.md'] as $path) {
            $files[$path] = trim(history8_git(['ls-tree', '--name-only', $commit, '--', $path])) === $path
                ? history8_git(['show', $commit . ':' . $path]) : '';
        }
        // Keep historical password/hash values in memory only; emit metadata and booleans.
        $literalFound = preg_match('/password_verify\(\s*([\x22\x27])([^\x22\x27]+)\1/',
            $files['admin/production_check.php'], $literal) === 1;
        preg_match_all('/\$2[aby]\$[0-9]{2}\$[.\/A-Za-z0-9]{53}/', $files['database/schema.sql'], $hashes);
        $matchingHashes = 0;
        if ($literalFound) {
            foreach ($hashes[0] as $hash) {
                if (password_verify($literal[2], $hash)) {
                    $matchingHashes++;
                }
            }
        }
        $rows[] = [
            'commit' => $commit,
            'diagnostic_literal_present' => $literalFound,
            'schema_account_insert_present' => preg_match('/INSERT\s+INTO\s+`?admins`?/i', $files['database/schema.sql']) === 1,
            'schema_contains_password_hash' => count($hashes[0]) > 0,
            'literal_matches_schema_hashes' => $matchingHashes,
            'readme_contains_same_literal' => $literalFound && str_contains($files['README.md'], $literal[2]),
        ];
        unset($files, $literal, $hashes, $hash);
    }
    $diagnostic = file_get_contents(dirname(__DIR__) . '/admin/production_check.php');
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $confirmed = array_filter($rows, static fn(array $row): bool => $row['literal_matches_schema_hashes'] > 0);
    $runtimeLiteral = preg_match('/password_verify\(\s*[\x22\x27]/', $diagnostic) === 1;
    $currentSeed = preg_match('/INSERT\s+INTO\s+`?admins`?/i', $schema) === 1;
    $passed = count($confirmed) > 0 && !$runtimeLiteral && !$currentSeed;
    echo json_encode([
        'status' => $passed ? 'PASS_READ_ONLY_HISTORY_AUDIT' : 'REVIEW_REQUIRED',
        'commits_reviewed' => count($rows),
        'history' => $rows,
        'current_diagnostic_password_literal' => $runtimeLiteral,
        'current_schema_account_seed' => $currentSeed,
        'database_accessed' => false,
        'actual_account_existence_or_password_verified' => false,
        'historical_password_can_verify_unchanged_hash' => count($confirmed) > 0,
        'production_decision' => 'ROTATION REQUIRED BEFORE PRODUCTION',
        'raw_credentials_printed' => false,
        'history_modified' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit($passed ? 0 : 1);
} catch (Throwable $exception) {
    fwrite(STDERR, "History audit failed safely; sensitive details suppressed.\n");
    exit(1);
}
