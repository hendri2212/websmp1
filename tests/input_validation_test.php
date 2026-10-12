<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
$cases = require __DIR__ . '/fixtures/stage3_content_cases.php';
$checks = 0;
$failures = 0;
function validation_check(bool $passed, string $label): void
{
    global $checks, $failures;
    $checks++;
    if (!$passed) {
        $failures++;
        echo '[FAIL] ' . $label . PHP_EOL;
    }
}
foreach ($cases as $module => $case) {
    $payload = $case['payload'];
    $payload[$case['field']] = 'Konten normal & " <b>teks</b>';
    if ($module === 'news') {
        $payload['slug'] = 'berita-normal_2026';
    }
    validation_check(input_validation_error($module, $payload) === '', $module . ' valid unchanged');
    $before = $payload;
    foreach (input_rules($module) as $field => $rule) {
        foreach ([[], ['text'], new stdClass(), 1, true, null] as $value) {
            validation_check(input_validation_error($module, array_replace($payload, [$field=>$value])) !== '', $module . '/' . $field . ' scalar');
        }
        if ($rule[1]) {
            foreach (['', '   ', "\t\n", "\u{00a0}\u{2003}"] as $blank) {
                validation_check(input_validation_error($module, array_replace($payload, [$field=>$blank])) !== '', $module . '/' . $field . ' required');
            }
        }
        if (($rule[2] ?? 'text') === 'text') {
            $limit = $rule[0];
            validation_check(input_validation_error($module, array_replace($payload, [$field=>str_repeat('A', $limit)])) === '', $module . '/' . $field . ' maximum accepted');
            validation_check(input_validation_error($module, array_replace($payload, [$field=>str_repeat('A', $limit + 1)])) !== '', $module . '/' . $field . ' maximum rejected');
            if ($limit < 1000) {
                validation_check(input_validation_error($module, array_replace($payload, [$field=>str_repeat('é', $limit)])) === '', $module . '/' . $field . ' unicode character limit');
            }
        }
    }
    validation_check($before === $payload, $module . ' validator never mutates text');
}
foreach (['2026-02-30','2025-02-29','0000-00-00','2026-13-01','2026-01-00','2026-1-01','2026-01-01extra'] as $date) {
    validation_check(input_datetime_value($date, true) === null, 'bad calendar date');
}
validation_check(input_datetime_value('2024-02-29', true) === '2024-02-29', 'leap day');
validation_check(input_display_date_is_valid('25 Mei 2023'), 'Indonesian accreditation date retained');
validation_check(input_display_date_is_valid('2023-05-25'), 'ISO accreditation date retained');
validation_check(!input_display_date_is_valid('30 Februari 2026'), 'invalid Indonesian calendar date');
foreach (['2026-02-30T10:00','2026-01-01T24:00','2026-01-01T12:60','yesterday','2026-01-01T12:00extra'] as $date) {
    validation_check(input_datetime_value($date) === null, 'bad datetime');
}
foreach (['2026-01-01T12:00','2026-01-01T12:00:00','2026-01-01 12:00:00'] as $date) {
    validation_check(input_datetime_value($date) === '2026-01-01 12:00:00', 'datetime formats normalized');
}
foreach (['12abc','1e2','1.5','01','+1',' 1','2147483648','-2147483649'] as $number) {
    validation_check(!input_integer_is_valid($number), 'strict signed integer');
}
foreach (['0','-1','2147483647','-2147483648'] as $number) {
    validation_check(input_integer_is_valid($number), 'valid signed integer');
}
$news = $cases['news']['payload'] + [];
$news['title'] = 'Berita';
foreach (['berita-normal','berita_2026','berita-é'] as $slug) {
    validation_check(input_validation_error('news', array_replace($news, ['slug'=>$slug])) === '', 'valid slug');
}
foreach (['../123','x/y','x?y','x#y','x y','x"y','--x','x--y'] as $slug) {
    validation_check(input_validation_error('news', array_replace($news, ['slug'=>$slug])) !== '', 'invalid slug');
}
foreach ([['home_teacher_limit'=>'3'], ['home_agenda_limit'=>'10'], ['theme_primary_color'=>'red'],
    ['current_academic_year'=>'2026/2026'], ['home_primary_button_url'=>'javascript:INERT'],
    ['home_quick_links'=>'Title | Description | javascript:INERT'], ['nav_visible'=>['/profil'=>[]]],
    ['name'=>'  ']] as $payload) {
    validation_check(input_validation_error('settings', $payload) !== '', 'settings invalid field');
}
validation_check(input_validation_error('settings', ['name'=>'Sekolah é & <b>nama</b>', 'home_teacher_limit'=>'24',
    'home_agenda_limit'=>'9','home_quick_links'=>'Profil | Keterangan & informasi | /profil',
    'nav_visible'=>['/profil'=>'1']]) === '', 'settings normal text retained');
foreach (['password'=>'short','role'=>'owner','username'=>str_repeat('x', 51)] as $field => $value) {
    validation_check(input_validation_error('admins', array_replace(['id'=>'','username'=>'test','role'=>'editor','password'=>' normal password '], [$field=>$value])) !== '', 'account invalid');
}
validation_check(input_validation_error('admins', ['id'=>'1','username'=>'test','role'=>'superadmin','password'=>'']) === '', 'blank password retains existing hash');
validation_check(input_validation_error('admins', ['id'=>'','username'=>'test','role'=>'editor','password'=>str_repeat('é', 37)]) !== '', 'bcrypt byte limit');
validation_check(input_validation_error('admins', ['id'=>'','username'=>'test','role'=>'editor','password'=>'        ']) !== '', 'blank-only password rejected without trimming');
validation_check(input_validation_error('contact_messages', ['message_id'=>'1','status'=>'bad']) !== '', 'invalid status');
$payload = ['name'=>'Visitor','email'=>'test@example.invalid','phone'=>'','subject'=>'Subject','message'=>str_repeat('A', 5000)];
validation_check(input_validation_error('contact', $payload) === '', 'long normal contact message not truncated');
validation_check(input_validation_error('contact', array_replace($payload, ['email'=>'invalid'])) !== '', 'invalid email');
$file = ['name'=>'dokumen sekolah é.pdf','tmp_name'=>'temporary','error'=>0,'size'=>100];
validation_check(input_validation_error('settings', [], ['file'=>$file]) === '', 'normal filename');
foreach (['../secret.pdf','..\\secret.pdf',"bad\0.pdf",str_repeat('x', 256)] as $name) {
    validation_check(input_validation_error('settings', [], ['file'=>array_replace($file,['name'=>$name])]) !== '', 'invalid filename');
}
validation_check(input_validation_error('settings', [], ['file'=>array_replace($file,['name'=>[]])]) !== '', 'array upload metadata');
validation_check(input_validation_error('settings', [], ['file'=>array_replace($file,['full_path'=>'../' . $file['name']])]) !== '', 'original upload path checked after PHP basename');
validation_check(input_validation_error('db_backup', ['backup_type'=>'']) !== '', 'empty backup enum rejected');
echo 'RESULT: ' . ($checks - $failures) . '/' . $checks . ' passed' . PHP_EOL;
exit($failures ? 1 : 0);
