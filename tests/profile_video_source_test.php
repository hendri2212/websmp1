<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$_ENV['APP_ENV'] = 'local';
$_ENV['APP_URL'] = 'http://127.0.0.1:8090';
require __DIR__ . '/../includes/functions.php';
$checks = 0;
$assert = static function (bool $ok) use (&$checks): void {
    if (!$ok) { throw new RuntimeException('Profile video source check failed.'); }
    $checks++;
};
$id = 'AbC_123-xYz';
foreach (['https://youtube.com/watch?v=' . $id, 'https://www.youtube.com/watch?v=' . $id . '&t=42',
    'https://youtu.be/' . $id . '?si=example', 'https://youtube.com/shorts/' . $id,
    'https://m.youtube.com/watch?v=' . $id, 'https://www.youtube.com/embed/' . $id] as $url) {
    $assert(youtube_video_id($url) === $id);
    $assert(video_embed_url($url) === 'https://www.youtube.com/embed/' . $id);
}
foreach (['https://youtube.com.evil.example/watch?v=' . $id, 'https://evilyoutube.com/watch?v=' . $id,
    'https://youtube.com@evil.example/watch?v=' . $id, 'https://evil.youtube.com/watch?v=' . $id,
    'https://youtu.be.evil.example/' . $id, 'https://youtube.com:444/watch?v=' . $id,
    '//youtube.com/watch?v=' . $id, 'javascript:alert(1)', '<iframe src="https://youtube.com"></iframe>',
    'https://youtube.com/watch?v[]=bad', 'https://youtube.com/watch?v=short',
    'https://youtube.com/shorts/' . $id . '/extra', '/public/video.mp4', 'https://example.org/video.mp4'] as $url) {
    $assert(youtube_video_id($url) === null);
    $assert(video_embed_url($url) === '');
}
$path = '/public/uploads/videos/videos-20261012000000-0123456789abcdef.mp4';
foreach ([$path, $path . '?v=1', 'http://127.0.0.1:8090' . $path . '?v=1'] as $url) {
    $canonical = profile_video_upload_path($url);
    $assert($canonical === $path);
    $assert(media_paths_in_value($canonical) === [ltrim($path, '/')]);
}
$assert(profile_video_upload_path('https://evil.example' . $path) === null);
$assert(profile_video_upload_path('/public/uploads/videos/../profile.mp4') === null);
$assert(input_validation_error('settings', ['home_profile_video_source'=>'forged']) !== '');
$assert(input_validation_error('settings', ['home_profile_video_source'=>'upload']) === '');
$assert(input_validation_error('settings', ['home_profile_video_source'=>'youtube']) === '');
echo "PASS: {$checks} profile video source checks; no database accessed.\n";
