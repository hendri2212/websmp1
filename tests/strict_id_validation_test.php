<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/repositories.php';

function db(): never
{
    throw new RuntimeException('Unexpected database access before validation');
}

$checks = 0;
$failures = 0;
$valid = [1, 123, PHP_INT_MAX, '1', '123', (string) PHP_INT_MAX];
$invalid = [null, '', '001', '0', 0, -1, '-1', '123abc', '123.4', '1e2', '../123', '123%00',
    "123\0", ' 123', '123 ', '+123', '123\n', "123\n", '123/4', [], ['123'], true, false, 123.0,
    (string) PHP_INT_MAX . '0', new stdClass()];
foreach (array_merge($valid, $invalid) as $index => $value) {
    $expected = $index < count($valid) ? (int) $value : null;
    $checks++;
    if (database_id($value) !== $expected) {
        $failures++;
        echo '[FAIL] Parser case ' . $index . PHP_EOL;
    }
}
$reads = ['admin_user','admin_news_row','admin_gallery_row','admin_announcement_row','admin_staff_row',
    'admin_agenda_row','admin_document_row','public_document_row','achievement_public_row','admin_achievement_row',
    'admin_extracurricular_row','admin_facility_row','admin_faq_row'];
$deletes = ['delete_admin_user','delete_news','delete_gallery_item','delete_announcement','delete_staff_member',
    'delete_agenda_item','delete_school_document','increment_document_download_count','delete_achievement',
    'delete_extracurricular','delete_facility','delete_faq','delete_contact_message'];
$saves = ['save_admin_user','save_news','save_gallery_item','save_announcement','save_staff_member',
    'save_agenda_item','save_school_document','save_achievement','save_extracurricular','save_facility','save_faq'];
foreach ($invalid as $value) {
    foreach (array_merge($reads, $deletes, $saves, ['update_contact_message_status']) as $function) {
        if ($value === null && in_array($function, $saves, true)) {
            continue;
        }
        $checks++;
        try {
            if (in_array($function, $saves, true)) {
                $function(['id'=>$value]);
            } elseif ($function === 'update_contact_message_status') {
                $function($value, 'selesai');
            } else {
                $function($value);
            }
            $failures++;
            echo '[FAIL] Accepted invalid ID: ' . $function . PHP_EOL;
        } catch (InvalidArgumentException $exception) {
            // Invalid IDs must fail before any database helper is called.
        } catch (Throwable $exception) {
            $failures++;
            echo '[FAIL] Validation ordering: ' . $function . ' ' . $exception->getMessage() . PHP_EOL;
        }
    }
}
foreach ($invalid as $value) {
    foreach (['_GET'=>['id','edit'], '_POST'=>['id','delete_id','message_id']] as $source => $keys) {
        foreach ($keys as $key) {
            if ($source === '_POST' && $key === 'id' && $value === '') {
                continue;
            }
            if (is_object($value)) {
                continue;
            }
            $code = 'require ' . var_export(__DIR__ . '/../includes/functions.php', true) . ';'
                . '$_GET=[];$_POST=[];$_SERVER["REQUEST_METHOD"]="POST";$' . $source . '='
                . var_export([$key=>$value], true) . ';require_valid_admin_request_ids();echo "HANDLER_RAN";';
            $process = proc_open([PHP_BINARY, '-r', $code], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
            $checks++;
            if ($status !== 0 || $output !== 'ID tidak valid.' || $errors !== '') {
                $failures++;
                echo '[FAIL] Request guard ' . $source . '/' . $key . PHP_EOL;
            }
        }
    }
}
echo 'RESULT: ' . ($checks - $failures) . '/' . $checks . ' passed' . PHP_EOL;
exit($failures ? 1 : 0);
