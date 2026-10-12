<?php

declare(strict_types=1);

function stage4_announcement_checks(PDO $pdo, array $run, array $anonymous, array $blocked, array $cases): void
{
    $case = $cases['announcements'];
    $path = '/admin/announcements.php';
    $label = 'TEST_TAHAP3_PENGUMUMAN_' . $run['tag'] . '_PUBLIC';
    $payload = content_payload($case, $label);
    $payload['content'] = 'Informasi sintetis baris pertama' . "\n" . 'Baris kedua';
    $payload['link_label'] = 'Baca informasi';
    $payload['link_url'] = '/profil';
    content_rejects($pdo, $run, $anonymous, $blocked, $path, 'announcements', $payload, 'Announcement public connection');
    $reply = content_submit($run, $path, $payload);
    $row = content_row($pdo, $case, $label);
    content_require($row !== null, 'Announcement fixture create failed.');
    $id = (int) $row['id'];
    $home = content_http($anonymous, '/');
    content_check($reply['status'] === 200 && $home['status'] === 200
        && str_contains($home['body'], $label) && str_contains($home['body'], 'Baris kedua')
        && str_contains($home['body'], 'href="' . $run['url'] . '/profil"'), 'Announcement CREATE -> database -> anonymous home with local link');
    $payload['id'] = (string) $id;
    $payload['title'] = $label . '_UPDATED';
    $payload['content'] = 'Isi diperbarui <script>/* inert fixture */</script> " &';
    $payload['link_label'] = 'Tautan " <uji>';
    $payload['link_url'] = 'https://example.invalid/info?a=1&b=2';
    content_submit($run, $path, $payload);
    $home = content_http($anonymous, '/');
    content_check(str_contains($home['body'], $payload['title'])
        && str_contains($home['body'], '&lt;script&gt;/* inert fixture */&lt;/script&gt;')
        && str_contains($home['body'], 'Tautan &quot; &lt;uji&gt;')
        && str_contains($home['body'], 'https://example.invalid/info?a=1&amp;b=2')
        && !str_contains($home['body'], '<script>/* inert fixture */</script>'), 'Announcement UPDATE -> public; text, label and URL escaped');
    $inactive = $payload;
    unset($inactive['is_active']);
    content_submit($run, $path, $inactive);
    content_check(!str_contains(content_http($anonymous, '/')['body'], $payload['title']), 'Inactive announcement hidden');
    content_submit($run, $path, $payload);
    content_check(str_contains(content_http($anonymous, '/')['body'], $payload['title']), 'Reactivated announcement visible');

    // Derive schedule fixtures from the same database clock used by the public query.
    $clock = $pdo->query("SELECT DATE_FORMAT(NOW() - INTERVAL 1 DAY, '%Y-%m-%dT%H:%i') AS past,
        DATE_FORMAT(NOW() + INTERVAL 1 DAY, '%Y-%m-%dT%H:%i') AS future")->fetch();
    foreach ([[$clock['future'], '', false], ['', $clock['past'], false],
        [$clock['past'], $clock['future'], true], ['', '', true]] as [$start, $end, $visible]) {
        content_submit($run, $path, array_replace($payload, ['starts_at'=>$start, 'ends_at'=>$end]));
        content_check(str_contains(content_http($anonymous, '/')['body'], $payload['title']) === $visible,
            'Announcement schedule ' . ($start ?: 'unbounded') . ' / ' . ($end ?: 'unbounded'));
    }
    $legacyUrl = $pdo->prepare('UPDATE announcements SET link_url = ? WHERE id = ?');
    foreach (['javascript:/* inert fixture */', '/profil" onmouseover="/* inert fixture */'] as $url) {
        $legacyUrl->execute([$url, $id]);
        $home = content_http($anonymous, '/');
        content_check(str_contains($home['body'], $payload['title']) && !str_contains($home['body'], $url),
            'Unsafe legacy announcement link omitted without hiding announcement');
    }
    content_submit($run, $path, $payload);
    $second = content_payload($case, $label . '_SECOND');
    $second['sort_order'] = '1';
    content_submit($run, $path, $second);
    $secondRow = content_row($pdo, $case, $second['title']);
    content_require($secondRow !== null, 'Second announcement unavailable.');
    $home = content_http($anonymous, '/');
    content_check(strpos($home['body'], $second['title']) < strpos($home['body'], $payload['title'])
        && str_contains($home['body'], 'data-carousel-next="home-announcement-carousel"'), 'Announcement sort order and independent carousel controls');
    $achievement = content_payload($cases['achievements'], $label . '_PRESTASI');
    content_submit($run, '/admin/achievements.php', $achievement);
    $achievementRow = content_row($pdo, $cases['achievements'], $achievement['title']);
    content_require($achievementRow !== null, 'Achievement fixture unavailable.');
    $home = content_http($anonymous, '/');
    content_check(str_contains($home['body'], $achievement['title']) && str_contains($home['body'], $payload['title'])
        && str_contains($home['body'], 'id="home-achievement-carousel"'), 'Existing achievement carousel retained alongside announcements');
    $setting = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
    $setting->execute(['siteHome', json_encode(['showAnnouncements'=>false], JSON_THROW_ON_ERROR)]);
    $settingId = (int) $pdo->lastInsertId();
    try {
        $home = content_http($anonymous, '/');
        content_check(!str_contains($home['body'], $payload['title']) && !str_contains($home['body'], 'id="home-announcement-carousel"'),
            'Existing home setting disables announcements');
    } finally {
        $pdo->prepare('DELETE FROM site_settings WHERE id = ? AND setting_key = ?')->execute([$settingId, 'siteHome']);
    }
    content_rejects($pdo, $run, $anonymous, $blocked, $path, 'announcements', $payload, 'Announcement UPDATE');
    foreach ([$id, (int) $secondRow['id']] as $deleteId) {
        content_submit($run, $path, ['delete_id'=>(string) $deleteId]);
    }
    $home = content_http($anonymous, '/');
    content_check(!str_contains($home['body'], $payload['title']) && !str_contains($home['body'], $second['title'])
        && !str_contains($home['body'], 'id="home-announcement-carousel"'), 'Deleted announcements and empty announcement band absent');
    content_submit($run, '/admin/achievements.php', ['delete_id'=>(string) $achievementRow['id']]);
}
