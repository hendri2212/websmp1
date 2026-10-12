<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

// This verifier has no database connection and never executes SQL from the backup.
function completeness_values(string $sql, int &$offset): array
{
    $values = [];
    $length = strlen($sql);
    $escapes = ['0'=>"\0", 'n'=>"\n", 'r'=>"\r", 't'=>"\t", 'b'=>"\x08", 'Z'=>"\x1a", "'"=>"'", '"'=>'"', '\\'=>'\\'];
    while ($offset < $length) {
        while ($offset < $length && ctype_space($sql[$offset])) { $offset++; }
        if (substr($sql, $offset, 4) === 'NULL') {
            $values[] = null;
            $offset += 4;
        } elseif (($sql[$offset] ?? '') === "'") {
            $offset++;
            $value = '';
            $closed = false;
            while ($offset < $length) {
                $char = $sql[$offset++];
                if ($char === '\\') {
                    if ($offset >= $length) { throw new RuntimeException('Truncated SQL escape.'); }
                    $escaped = $sql[$offset++];
                    if (!array_key_exists($escaped, $escapes)) { throw new RuntimeException('Unsupported SQL escape.'); }
                    $value .= $escapes[$escaped];
                } elseif ($char === "'") {
                    if (($sql[$offset] ?? '') === "'") { $value .= "'"; $offset++; }
                    else { $closed = true; break; }
                } else { $value .= $char; }
            }
            if (!$closed) { throw new RuntimeException('Unclosed SQL value.'); }
            $values[] = $value;
        } else { throw new RuntimeException('Unexpected SQL value token.'); }
        while ($offset < $length && ctype_space($sql[$offset])) { $offset++; }
        if (substr($sql, $offset, 2) === ');') { $offset += 2; return $values; }
        if (($sql[$offset] ?? '') !== ',') { throw new RuntimeException('Invalid SQL separator.'); }
        $offset++;
    }
    throw new RuntimeException('Truncated SQL row.');
}

function completeness_rows(array $rows): array
{
    $normalized = [];
    foreach ($rows as $row) {
        ksort($row);
        $normalized[] = json_encode(array_map(static fn($value)=>$value === null ? null : (string) $value, $row), JSON_THROW_ON_ERROR);
    }
    sort($normalized, SORT_STRING);
    return $normalized;
}

$checks = [];
$check = static function (bool $passed, string $label) use (&$checks): void { $checks[] = compact('passed','label'); };
try {
    $expected = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    $type = $argv[3];
    if ($type === 'full') {
        $zip = new ZipArchive();
        if ($zip->open($argv[2]) !== true) { throw new RuntimeException('Unreadable ZIP.'); }
        $manifest = json_decode($zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $sqlName = 'database/backup-' . $manifest['database'] . '.sql';
        $sql = $zip->getFromName($sqlName);
        $check(is_string($sql), 'ZIP contains full database data SQL');
        $check(hash('sha256', (string) $zip->getFromName('database/schema.sql')) === $expected['schema_hash'], 'Installer schema bytes preserved (not executed)');
        foreach ($expected['files'] as $name => $hash) {
            $bytes = $zip->getFromName($name);
            $check(is_string($bytes) && hash('sha256', $bytes) === $hash, 'Upload bytes match SHA-256: ' . $name);
        }
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) { $names[] = $zip->getNameIndex($i); }
        $allowed = array_merge(['manifest.json', 'database/schema.sql', $sqlName], array_keys($expected['files']));
        sort($names); sort($allowed);
        $check($names === $allowed, 'ZIP has exactly expected database/manifest/upload entries; no configuration or unexpected files');
        $counts = [];
        foreach ($manifest['tables'] as $table) { $counts[$table['table']] = (int) $table['count']; }
        $expectedCounts = array_map(static fn($table)=>count($table['rows']), $expected['tables']);
        ksort($counts); ksort($expectedCounts);
        $check($counts === $expectedCounts, 'Manifest table counts match database immediately before export');
        $zip->close();
    } elseif ($type === 'sql') { $sql = file_get_contents($argv[2]); }
    else { throw new RuntimeException('Unsupported backup type.'); }
    if (!is_string($sql)) { throw new RuntimeException('Unreadable SQL.'); }
    $parsed = [];
    preg_match_all('/^INSERT INTO `([a-zA-Z0-9_]+)` \(([^\r\n]+)\) VALUES \(/m', $sql, $inserts, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($inserts as $insert) {
        $table = $insert[1][0];
        $columns = explode(', ', $insert[2][0]);
        foreach ($columns as &$column) {
            if (!preg_match('/\A`([a-zA-Z0-9_]+)`\z/', $column, $match)) { throw new RuntimeException('Unexpected SQL identifier.'); }
            $column = $match[1];
        }
        unset($column);
        $offset = $insert[0][1] + strlen($insert[0][0]);
        $values = completeness_values($sql, $offset);
        if (count($columns) !== count($values)) { throw new RuntimeException('SQL column/value mismatch.'); }
        $parsed[$table][] = array_combine($columns, $values);
    }
    preg_match_all('/^CREATE TABLE `([a-zA-Z0-9_]+)` /m', $sql, $creates);
    $names = $creates[1]; $expectedNames = array_keys($expected['tables']);
    sort($names); sort($expectedNames);
    $check($names === $expectedNames, 'Every base table represented once, including empty legacy PPDB and testing marker');
    $check(!array_diff(array_keys($parsed), $expectedNames), 'No unexpected data tables in SQL');
    foreach ($expected['tables'] as $name => $table) {
        $check(str_contains($sql, $table['schema'] . ';'), $name . ': complete SHOW CREATE TABLE structure retained');
        $check(completeness_rows($parsed[$name] ?? []) === completeness_rows($table['rows']), $name . ': exact row count and every column value preserved');
    }
    $check(str_contains($sql, 'SET FOREIGN_KEY_CHECKS=0;') && str_contains($sql, 'SET FOREIGN_KEY_CHECKS=1;'), 'Foreign-key guard statements included');
} catch (Throwable $exception) {
    $check(false, 'Read-only verifier failed: ' . $exception->getMessage());
}
echo json_encode(['checks'=>$checks], JSON_THROW_ON_ERROR);
exit(count(array_filter($checks, static fn($check)=>!$check['passed'])) ? 1 : 0);
