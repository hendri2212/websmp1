<?php

declare(strict_types=1);

function stage3_upload_apache_checks(array $run, array $samples, array $privatePaths = []): void
{
    $binary = 'C:/xampp/apache/bin/httpd.exe';
    content_require(is_file($binary), 'Apache binary unavailable; executable protection needs separate verification.');
    $socket = @stream_socket_client('tcp://127.0.0.1:8089', $code, $message, 1);
    if (is_resource($socket)) {
        fclose($socket);
        throw new RuntimeException('Isolated Apache port occupied.');
    }
    $root = str_replace('\\', '/', $run['snapshot']);
    $probeDir = $root . '/public/uploads/security-probes';
    if (!is_dir($probeDir)) {
        mkdir($probeDir, 0700);
    }
    $marker = 'TEST_TAHAP3_SAFE_EXECUTED';
    $script = '<?php echo "' . $marker . '"; ?>';
    file_put_contents($root . '/safe-control.php', $script);
    $probeNames = ['canary.php','canary.phtml','canary.phar','canary.cgi','canary.pl','canary.asp','canary.aspx','canary.jsp',
        'upper-canary.PHP','canary.php5','canary.phps','canary.php.png'];
    foreach ($probeNames as $name) {
        file_put_contents($probeDir . '/' . $name, $script);
    }
    $config = 'ServerRoot "C:/xampp/apache"' . "\n"
        . "Listen 127.0.0.1:8089\nServerName 127.0.0.1:8089\n"
        . 'PidFile "' . $root . '/apache-test.pid"' . "\n"
        . 'ErrorLog "' . $root . '/apache-error.log"' . "\nLogLevel warn\n"
        . "LoadModule authz_core_module modules/mod_authz_core.so\nLoadModule authz_host_module modules/mod_authz_host.so\n"
        . "LoadModule dir_module modules/mod_dir.so\nLoadModule mime_module modules/mod_mime.so\nLoadModule rewrite_module modules/mod_rewrite.so\n"
        . "LoadFile C:/xampp/php/php8ts.dll\nLoadFile C:/xampp/php/libpq.dll\nLoadFile C:/xampp/php/libsqlite3.dll\n"
        . "LoadModule php_module C:/xampp/php/php8apache2_4.dll\nPHPIniDir C:/xampp/php\n"
        . "TypesConfig C:/xampp/apache/conf/mime.types\nDirectoryIndex index.php\n"
        . 'DocumentRoot "' . $root . '"' . "\n"
        . "<Directory />\nAllowOverride None\nRequire all denied\n</Directory>\n"
        . '<Directory "' . $root . '">' . "\nAllowOverride All\nRequire all granted\nOptions -Indexes\n</Directory>\n"
        . "<FilesMatch \"\\.php$\">\nSetHandler application/x-httpd-php\n</FilesMatch>\n"
        . "<FilesMatch \"\\.phps$\">\nSetHandler application/x-httpd-php-source\n</FilesMatch>\n";
    $configPath = $root . '/apache-test.conf';
    file_put_contents($configPath, $config);
    // -X keeps this isolated listener in one debug process, not a service or normal server instance.
    $process = proc_open([$binary,'-X','-f',$configPath], [0=>['pipe','r'],1=>['file',$root . '/apache-out.log','a'],2=>['file',$root . '/apache-err.log','a']], $pipes, $root, null, ['bypass_shell'=>true]);
    content_require(is_resource($process), 'Cannot start isolated Apache.');
    fclose($pipes[0]);
    try {
        $ready = false;
        for ($i=0; $i<100; $i++) {
            $socket = @stream_socket_client('tcp://127.0.0.1:8089', $code, $message, 0.1);
            if (is_resource($socket)) {
                fclose($socket);
                $ready = true;
                break;
            }
            if (!proc_get_status($process)['running']) {
                break;
            }
            usleep(100000);
        }
        content_require($ready, 'Isolated Apache failed to start; inspect private logs.');
        $apache = array_replace($run, ['url'=>'http://127.0.0.1:8089','jar'=>$root . '/apache.cookies']);
        $control = content_http($apache, '/safe-control.php');
        content_require($control['status'] === 200 && $control['body'] === $marker, 'Apache control cannot prove an active PHP handler.');
        content_check(true, 'Apache safe echo control confirms PHP is active outside upload directory');
        $observations = [];
        foreach ($probeNames as $name) {
            $reply = content_http($apache, '/public/uploads/security-probes/' . $name);
            $observations[] = ['name'=>$name,'status'=>$reply['status'],'content_type'=>$reply['headers']['content-type'] ?? '',
                'source_marker_visible'=>str_contains($reply['body'], $marker),'echo_executed'=>$reply['body'] === $marker];
            content_check(in_array($reply['status'], [403,404], true), 'Apache original upload .htaccess denies script-like file ' . $name);
            content_check($reply['body'] !== $marker && !str_ends_with($reply['body'], $marker), 'Apache upload canary ' . $name . ' not executed');
        }
        foreach (['/public/uploads/security-probes/canary.php/extra','/public/uploads/security-probes/','/storage/uploads/documents/','/config/database.php','/public/uploads/.htaccess'] as $path) {
            $reply = content_http($apache, $path);
            content_check(in_array($reply['status'], [403,404], true), 'Apache denies upload PATH_INFO/listing or private path ' . $path);
        }
        foreach (array_slice($samples, 0, 8) as $path) {
            $reply = content_http($apache, $path);
            content_check($reply['status'] === 200 && hash('sha256', $reply['body']) === hash_file('sha256', $root . $path), 'Apache serves stored image as static bytes, not PHP');
        }
        foreach ($privatePaths as $path) {
            $reply = content_http($apache, $path);
            content_check(in_array($reply['status'], [400,403,404], true), 'Apache denies private legacy path: ' . $path);
            content_check(!str_contains($reply['body'], 'TEST_TAHAP4_PRIVATE_'), 'Apache returns no private legacy bytes: ' . $path);
        }
        file_put_contents($root . '/apache-observations.json', json_encode(['php_control_verified'=>true,'probes'=>$observations], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    } finally {
        proc_terminate($process);
        proc_close($process);
        $socket = @stream_socket_client('tcp://127.0.0.1:8089', $code, $message, 1);
        if (is_resource($socket)) {
            fclose($socket);
            content_check(false, 'Isolated Apache listener still active after termination; inspect owned process.');
        } else {
            content_check(true, 'Isolated Apache stopped; no listener on 8089');
        }
    }
}
