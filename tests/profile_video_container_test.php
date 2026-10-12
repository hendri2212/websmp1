<?php
declare(strict_types=1);
// Supply a directory with synthetic FFmpeg/MediaRecorder valid.mp4, valid.mov and fragmented.mp4.
// Fixtures are local test artifacts, never deployment files; FFmpeg is not a runtime dependency.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/../includes/upload_security.php';
$checks = 0; $paths = [];
function video_check(bool $passed, string $label): void {
    global $checks;
    $checks++;
    if (!$passed) { throw new RuntimeException('FAIL: ' . $label); }
}
function video_fixture_check(string $bytes, bool $expected, string $label, bool $mov = false): void {
    global $paths;
    $path = tempnam(sys_get_temp_dir(), 'school-video-');
    $paths[] = $path;
    file_put_contents($path, $bytes);
    video_check(uploaded_video_has_basic_structure($path, $mov) === $expected, $label);
}
function video_atom(string $type, string $payload): string { return pack('N', strlen($payload) + 8) . $type . $payload; }
try {
    $directory = realpath($argv[1] ?? '');
    if (!$directory || !is_dir($directory)) { throw new RuntimeException('Provide the isolated synthetic video fixture directory.'); }
    foreach (['valid.mp4', 'valid.mov', 'fragmented.mp4'] as $file) {
        $path = $directory . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path)) { throw new RuntimeException('Required synthetic fixture missing: ' . $file); }
        $bytes = file_get_contents($path);
        video_fixture_check($bytes, true, 'real encoded fixture ' . $file, str_ends_with($file, '.mov'));
        video_fixture_check(substr($bytes, 0, -15), false, 'truncated fixture ' . $file, str_ends_with($file, '.mov'));
        $budget = 10000;
        $boxes = upload_video_boxes($bytes, 0, strlen($bytes), $budget);
        $mdat = upload_video_box($boxes, 'mdat');
        $zeroed = substr_replace($bytes, str_repeat("\0", $mdat['end'] - $mdat['data']), $mdat['data'], $mdat['end'] - $mdat['data']);
        video_fixture_check($zeroed, false, 'corrupt encoded media ' . $file, str_ends_with($file, '.mov'));
    }
    $forged = video_atom('ftyp', 'isom' . pack('N', 0) . 'isommp42')
        . video_atom('moov', 'hdlr' . str_repeat("\0", 8) . 'vide' . 'stsd')
        . video_atom('mdat', str_repeat("\0", 32));
    $path = tempnam(sys_get_temp_dir(), 'school-video-'); $paths[] = $path; file_put_contents($path, $forged);
    video_check((new finfo(FILEINFO_MIME_TYPE))->file($path) === 'video/mp4', 'negative fixture passes actual MIME gate');
    video_fixture_check($forged, false, 'original MIME-valid fake-track regression');
    video_fixture_check($forged, false, 'fake-track MOV regression', true);
    video_fixture_check(video_atom('ftyp', 'isom' . pack('N', 0)), false, 'initialization without movie/media');
    video_fixture_check(pack('N', 0xffffffff) . 'moov' . str_repeat("\0", 32), false, 'oversized box');
    video_fixture_check(pack('N', 1) . 'moov' . pack('NN', 1, 16) . str_repeat("\0", 32), false, 'extended size overflow');
    video_fixture_check('safe invalid video test', false, 'non-container data');
    $bytes = file_get_contents($directory . '/valid.mp4');
    $budget = 10000; $boxes = upload_video_boxes($bytes, 0, strlen($bytes), $budget);
    $moov = upload_video_box($boxes, 'moov');
    $children = upload_video_boxes($bytes, $moov['data'], $moov['end'], $budget);
    $trak = upload_video_box($children, 'trak');
    video_fixture_check(substr_replace($bytes, pack('N', 0x7fffffff), $trak['start'], 4), false, 'nested track size outside parent');
    $position = strpos($bytes, 'stco');
    video_check($position !== false, 'classic fixture has chunk offsets');
    video_fixture_check(substr_replace($bytes, pack('N', strlen($bytes) + 1), $position + 12, 4), false, 'sample points outside media');
    $position = strpos($bytes, 'stsz');
    video_check($position !== false, 'classic fixture has sample sizes');
    video_fixture_check(substr_replace($bytes, pack('N', 0xffffffff), $position + 12, 4), false, 'unbounded sample count rejected');
    $position = strpos($bytes, 'avcC');
    video_check($position !== false, 'classic fixture has codec configuration');
    video_fixture_check(substr_replace($bytes, "\0", $position + 4, 1), false, 'invalid AVC configuration version');
    video_fixture_check($bytes . 'trailing-junk', false, 'trailing garbage');
    video_fixture_check($bytes . substr($bytes, $moov['start'], $moov['end'] - $moov['start']), false, 'duplicate movie header');
    $path = tempnam(sys_get_temp_dir(), 'school-video-'); $paths[] = $path;
    $stream = fopen($path, 'wb'); ftruncate($stream, 20 * 1048576 + 1); fclose($stream);
    video_check(!uploaded_video_has_basic_structure($path), '20 MiB limit unchanged');
    echo "PASS: $checks video container checks; no database accessed.\n";
} finally {
    foreach ($paths as $path) { if (is_file($path)) { unlink($path); } }
}
