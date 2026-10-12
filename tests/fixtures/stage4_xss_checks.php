<?php

declare(strict_types=1);

function stage4_xss_checks(PDO $pdo, array $run, array $anonymous, array $cases, array &$ownedSettings): void
{
    $marker = 'STAGE4_XSS_' . substr($run['tag'], -12);
    $probe = '"><b data-stage3-probe="' . $marker . '">TEXT</b><script>/*' . $marker . '*/</script>&\'';
    $news = $pdo->query('SELECT * FROM news ORDER BY id LIMIT 1')->fetch();
    $slugUpdate = $pdo->prepare('UPDATE news SET slug = ? WHERE id = ?');
    foreach (['legacy " quote & slash/name', 'legacy-single-quote\'-name', 'legacy-' . rawurldecode('%C3%A9')] as $slug) {
        $slugUpdate->execute([$slug, $news['id']]);
        try {
            $reply = content_http($anonymous, '/berita/' . rawurlencode($slug));
            content_check($reply['status'] === 200 && str_contains($reply['body'], htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8')),
                'Encoded legacy slug remains one URL segment and resolves exact record');
        } finally {
            $slugUpdate->execute([$news['slug'], $news['id']]);
        }
    }
    foreach (['news','gallery','staff','achievements','facilities','extracurriculars'] as $module) {
        $case = $cases[$module];
        $original = $pdo->query('SELECT * FROM `' . $case['table'] . '` ORDER BY id LIMIT 1')->fetch();
        $column = $module === 'staff' ? 'photo_path' : 'image_path';
        $update = $pdo->prepare('UPDATE `' . $case['table'] . '` SET `' . $column . '` = ? WHERE id = ?');
        $update->execute(['/public/probe.png" data-stage3-probe="' . $marker . '" onerror="/*inert*/', $original['id']]);
        try {
            foreach ([$case['public'], '/admin/' . $module . '.php?edit=' . $original['id']] as $path) {
                $reply = content_http(str_starts_with($path, '/admin/') ? $run : $anonymous, $path);
                content_check($reply['status'] === 200 && input_no_probe_nodes($reply['body'], $marker), $module . ' legacy poisoned database path: escaped at render boundary ' . $path);
            }
        } finally {
            $update->execute([$original[$column], $original['id']]);
        }
    }
    $defaults = require dirname(__DIR__, 2) . '/config/site_data.php';
    $upsert = $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $profile = $defaults['schoolProfile'];
    $profile['name'] = $probe;
    $profile['description'] = $probe;
    $profile['logoPath'] = '/public/logo.png" data-stage3-probe="' . $marker . '" onerror="/*inert*/';
    $principal = $defaults['principalMessage'];
    $principal['name'] = $probe;
    $principal['photoPath'] = $profile['logoPath'];
    $home = $defaults['siteHome'];
    $home['heroTitle'] = $probe;
    $home['profileVideoUrl'] = 'javascript:/*' . $marker . '*/';
    $home['primaryButtonUrl'] = 'javascript:/*' . $marker . '*/';
    $home['secondaryButtonUrl'] = 'data:text/html,<b>INERT</b>';
    foreach (['schoolProfile'=>$profile,'principalMessage'=>$principal,'siteHome'=>$home] as $key=>$value) {
        $upsert->execute([$key,json_encode($value, JSON_THROW_ON_ERROR)]);
    }
    $ownedSettings = $pdo->query('SELECT id,setting_key FROM site_settings ORDER BY id')->fetchAll();
    foreach (['/','/profil','/admin/settings.php','/dokumen','/agenda','/prestasi'] as $path) {
        $reply = content_http(str_starts_with($path, '/admin/') ? $run : $anonymous, $path);
        content_check($reply['status'] === 200 && str_contains($reply['body'], htmlspecialchars($probe, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            && input_no_probe_nodes($reply['body'], $marker), 'Legacy profile/name/logo/principal: escaping across header/footer and body ' . $path);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($reply['body'], LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $unsafe = false;
        foreach ((new DOMXPath($document))->query('//*[@href or @src]') as $node) {
            foreach (['href','src'] as $attribute) {
                $value = $node->getAttribute($attribute);
                if (preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                    $unsafe = true;
                }
            }
        }
        content_check(!$unsafe, 'Legacy settings cannot render executable URL schemes ' . $path);
    }
    foreach (['existing_logo_path'=>'logoPath','existing_principal_photo_path'=>'photoPath','existing_accreditation_file_path'=>'filePath'] as $field=>$key) {
        $before = $pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll();
        $reply = content_submit($run, '/admin/settings.php', ['name'=>'TEST_TAHAP3_XSS_' . $run['tag'], $field=>'/public/forged.png']);
        $ownedSettings = $pdo->query('SELECT id,setting_key FROM site_settings ORDER BY id')->fetchAll();
        $values = [];
        foreach ($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $setting) {
            $values[$setting['setting_key']] = json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR);
        }
        $section = $field === 'existing_logo_path' ? 'schoolProfile' : ($field === 'existing_principal_photo_path' ? 'principalMessage' : 'schoolAccreditation');
        $old = [];
        foreach ($before as $setting) {
            $old[$setting['setting_key']] = json_decode($setting['setting_value'], true, 512, JSON_THROW_ON_ERROR);
        }
        content_check($reply['status'] === 200 && ($values[$section][$key] ?? '') === ($old[$section][$key] ?? $defaults[$section][$key] ?? ''), 'Settings forged hidden path ignored: ' . $field);
    }
    foreach (['javascript:/*INERT*/','data:text/html,INERT','https://example.invalid/profile'] as $url) {
        $before = $pdo->query('SELECT * FROM site_settings ORDER BY id')->fetchAll();
        $reply = content_submit($run, '/admin/settings.php', ['home_profile_video_url'=>$url]);
        $ownedSettings = $pdo->query('SELECT id,setting_key FROM site_settings ORDER BY id')->fetchAll();
        content_check($reply['status'] === 200 && ($url === 'https://example.invalid/profile'
            ? true : $pdo->query('SELECT * FROM site_settings ORDER BY id')->fetchAll() === $before), 'Settings video URL policy: ' . $url);
    }
}
