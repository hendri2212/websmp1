<?php

function stage4_media_lifecycle_checks(array $run): void
{
    $probe = $run['snapshot'] . '/media-lifecycle-probe.php';
    copy(__DIR__ . '/stage4_media_lifecycle_probe.php', $probe);
    $process = proc_open([PHP_BINARY, $probe, dirname(__DIR__) . '/stage3_database_preflight.php'],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['file',$run['snapshot'] . '/media-lifecycle-probe.err.log','a']], $pipes, $run['snapshot']);
    content_require(is_resource($process), 'Isolated lifecycle probe could not start.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);
    content_require($code === 0, 'Isolated lifecycle probe failed; review private internal log.');
    foreach (json_decode($output, true, 512, JSON_THROW_ON_ERROR) as $check) {
        content_check($check['passed'], 'lifecycle ' . $check['label']);
    }
    file_put_contents($run['snapshot'] . '/media-lifecycle-result.json', $output);
}
