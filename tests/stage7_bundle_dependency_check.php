<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

function dependency_literal(string $value): string
{
    $quote = $value[0];
    $value = substr($value, 1, -1);
    return $quote === "'" ? str_replace(["\\'", "\\\\"], ["'", "\\"], $value) : stripcslashes($value);
}

function dependency_path(string $caller, string $relative): ?string
{
    $segments = explode('/', dirname($caller) . '/' . $relative);
    $result = [];
    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') { continue; }
        if ($segment === '..') {
            if (!$result) { return null; }
            array_pop($result);
        } else { $result[] = $segment; }
    }
    return implode('/', $result);
}

try {
    if ($argc !== 2 || !class_exists('ZipArchive')) { throw new RuntimeException('ZIP input/extension required'); }
    $archivePath = $argv[1];
    $before = hash_file('sha256', $archivePath);
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) { throw new RuntimeException('ZIP unavailable'); }
    $entries = []; $php = [];
    try {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name) || isset($entries[$name]) || preg_match('~(^/|^[A-Za-z]:|\\\\|(^|/)\.\.?(/|$)|[\x00-\x1f])~', $name)) { throw new RuntimeException('Unsafe entry'); }
            $entries[$name] = true;
            if (str_ends_with($name, '.php') || str_ends_with($name, '.css')) {
                $source = $zip->getFromIndex($i);
                if (!is_string($source)) { throw new RuntimeException('Unreadable entry'); }
                $php[$name] = $source;
            }
        }
        $edges = []; $unresolved = []; $assets = []; $views = [];
        $counts = ['require'=>0, 'require_once'=>0, 'include'=>0, 'include_once'=>0];
        $types = [T_REQUIRE=>'require', T_REQUIRE_ONCE=>'require_once', T_INCLUDE=>'include', T_INCLUDE_ONCE=>'include_once'];
        foreach ($php as $file => $source) {
            if (str_ends_with($file, '.css')) {
                preg_match_all('~url\(\s*["\']?([^\s)"\']+)["\']?\s*\)~i', $source, $urls);
                foreach ($urls[1] as $url) {
                    if (preg_match('~^(data:|https?://|//|#)~i', $url)) { continue; }
                    $url = explode('?', explode('#', $url)[0])[0];
                    $target = str_starts_with($url, '/') ? ltrim($url, '/') : dependency_path($file, $url);
                    $assets[] = ['caller'=>$file, 'target'=>$target, 'exists'=>$target !== null && isset($entries[$target])];
                }
                continue;
            }
            $tokens = token_get_all($source, TOKEN_PARSE);
            for ($i = 0, $length = count($tokens); $i < $length; $i++) {
                $token = $tokens[$i];
                if (!is_array($token)) { continue; }
                if ($file === 'index.php' && $token[0] === T_VARIABLE && $token[1] === '$view') {
                    $assignment = [];
                    for ($j = $i + 1; $j < $length && $tokens[$j] !== ';'; $j++) {
                        $part = $tokens[$j];
                        if (is_array($part) && in_array($part[0], [T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true)) { continue; }
                        $assignment[] = $part;
                    }
                    if (count($assignment) === 2 && $assignment[0] === '=' && is_array($assignment[1]) && $assignment[1][0] === T_CONSTANT_ENCAPSED_STRING) {
                        $views[] = dependency_literal($assignment[1][1]);
                    }
                }
                if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $literal = dependency_literal($token[1]);
                    if (preg_match('~^/?(public/[A-Za-z0-9_./-]+\.(?:css|js|png|jpg|jpeg|webp|gif|svg|ico|woff2?|ttf))(?:[?#].*)?$~i', $literal, $asset)) {
                        $assets[] = ['caller'=>$file, 'target'=>$asset[1], 'exists'=>isset($entries[$asset[1]])];
                    }
                }
                if (!isset($types[$token[0]])) { continue; }
                $type = $types[$token[0]]; $counts[$type]++;
                $expression = [];
                for ($j = $i + 1; $j < $length && $tokens[$j] !== ';'; $j++) {
                    $part = $tokens[$j];
                    if (is_array($part) && in_array($part[0], [T_WHITESPACE,T_COMMENT,T_DOC_COMMENT], true)) { continue; }
                    $expression[] = $part;
                }
                if (count($expression) === 3 && is_array($expression[0]) && $expression[0][0] === T_DIR && $expression[1] === '.'
                    && is_array($expression[2]) && $expression[2][0] === T_CONSTANT_ENCAPSED_STRING) {
                    $target = dependency_path($file, dependency_literal($expression[2][1]));
                    $edges[] = ['caller'=>$file, 'line'=>$token[2], 'type'=>$type, 'target'=>$target, 'exists'=>$target !== null && isset($entries[$target])];
                } elseif ($file === 'index.php' && count($expression) === 5 && is_array($expression[0]) && $expression[0][0] === T_DIR
                    && $expression[1] === '.' && is_array($expression[2]) && $expression[2][0] === T_CONSTANT_ENCAPSED_STRING
                    && dependency_literal($expression[2][1]) === '/' && $expression[3] === '.' && is_array($expression[4])
                    && $expression[4][0] === T_VARIABLE && $expression[4][1] === '$view') {
                    $edges[] = ['caller'=>$file, 'line'=>$token[2], 'type'=>$type, 'target'=>'(finite $view assignments)', 'exists'=>true, 'dynamic'=>true];
                } else { $unresolved[] = ['caller'=>$file,'line'=>$token[2],'type'=>$type]; }
            }
            preg_match_all('~(?:src|href)\s*=\s*["\'](/?public/[^"\'<>]+)["\']~i', $source, $html);
            foreach ($html[1] as $url) {
                $target = ltrim(explode('?', explode('#', $url)[0])[0], '/');
                $assets[] = ['caller'=>$file, 'target'=>$target, 'exists'=>isset($entries[$target])];
            }
        }
        $views = array_values(array_unique($views));
        $missingViews = array_values(array_filter($views, fn($view)=>!isset($entries[$view])));
        $missingEdges = array_values(array_filter($edges, fn($edge)=>!$edge['exists']));
        $missingAssets = array_values(array_filter($assets, fn($asset)=>!$asset['exists']));
        $uniqueAssets = array_values(array_unique(array_column($assets, 'target')));
        $unchanged = $before === hash_file('sha256', $archivePath);
        $passed = !$unresolved && !$missingEdges && !$missingViews && !$missingAssets && $unchanged;
        $result = ['status'=>$passed?'PASS_SOURCE_DEPENDENCIES':'FAIL', 'archive_sha256'=>$before, 'archive_unchanged'=>$unchanged,
            'archive_files'=>count($entries), 'php_files'=>count(array_filter(array_keys($php),fn($file)=>str_ends_with($file,'.php'))),
            'include_token_counts'=>$counts, 'static_includes'=>count(array_filter($edges,fn($edge)=>empty($edge['dynamic']))),
            'dynamic_include_sites'=>count(array_filter($edges,fn($edge)=>!empty($edge['dynamic']))), 'dynamic_view_targets'=>count($views),
            'unresolved_includes'=>$unresolved, 'missing_includes'=>$missingEdges, 'missing_views'=>$missingViews,
            'asset_references'=>count($assets), 'unique_local_assets'=>$uniqueAssets, 'missing_assets'=>$missingAssets,
            'source_missing_runtime_dependencies'=>count($missingEdges)+count($missingViews)+count($missingAssets),
            'external_requirements'=>'Explicit environment, compatible DB/extensions/hosting, approved persistent media/PDF and private writable directories require separate provisioning; content-driven/external URLs not certified by source scan.',
            'optional_backup_schema'=>'database/schema.sql excluded; guarded optional read in backup handler, not require/include. Actual DB dump generated separately; backup runtime retest pending.',
            'application_executed'=>false, 'database_accessed'=>false];
    } finally { $zip->close(); }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($passed ? 0 : 1);
} catch (Throwable $exception) {
    echo json_encode(['status'=>'SCAN_FAILED','exception_type'=>get_class($exception),'scanner_line'=>$exception->getLine(),
        'details'=>'Suppressed; no source, secret or exception detail printed.']) . PHP_EOL;
    exit(1);
}
