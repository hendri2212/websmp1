<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = dirname(__DIR__);
$apache = 'C:/xampp/apache/bin/httpd.exe';
$process = null;
$run = null;
$checks = 0;
$sensitiveObservations = [];
$exitCode = 0;
$port = null;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('Failed assertion: ' . $label);
    }
};
$write = static function (string $path, string $bytes): void {
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true)) {
        throw new RuntimeException('Cannot create fixture directory');
    }
    if (file_put_contents($path, $bytes) === false) {
        throw new RuntimeException('Cannot create fixture');
    }
};

try {
    $assert(is_file($apache), 'local Apache available');
    $parent = $root . '/storage/testing';
    $assert(is_dir($parent) && !is_link($parent) && !is_link($root . '/storage'), 'private fixture parent');
    $run = $parent . '/stage6-apache-' . bin2hex(random_bytes(8));
    $site = $run . '/site';
    mkdir($site, 0700, true);
    // Copy only runtime source; never copy real .env, repository metadata or persistent uploads.
    foreach (['admin', 'config', 'includes', 'pages', 'templates'] as $folder) {
        foreach (new DirectoryIterator($root . '/' . $folder) as $file) {
            if ($file->isFile() && !$file->isLink() && $file->getExtension() === 'php') {
                $write($site . '/' . $folder . '/' . $file->getFilename(), file_get_contents($file->getPathname()));
            }
        }
    }
    foreach (['index.php', '.htaccess', '.user.ini', 'public/uploads/.htaccess', 'storage/.htaccess', 'public/assets/css/style.css', 'public/logo-sekolah.png'] as $path) {
        $write($site . '/' . $path, file_get_contents($root . '/' . $path));
    }
    // The sole substituted boundary guarantees that no request can open any database.
    $write($site . '/includes/database.php', '<?php function db(): ?PDO { return null; } function db_available(): bool { return false; }');
    $sentinel = 'STAGE6_PRIVATE_HTTP_BYTES';
    $sensitive = ['.git/HEAD', '.git/config', '.env', '.env.production', '.hidden/private.txt',
        'public/.hidden/private.txt', 'tests/probe.php', 'database/maintenance_legacy_schema.php',
        'database/seed_final_content.php', 'database/schema.sql', 'backup/full.zip', 'backups/full.zip',
        'dumps/archive.bin', 'logs/events.txt', 'nested/backups/full.zip', 'storage/private.txt',
        'storage/uploads/ppdb/historical.pdf', 'source_docs/source.pdf', 'dump.sql', 'DUMP.SQL',
        'dump.sql.gz', 'archive.zip', 'dump.tar.gz', 'server.log', 'server.log.1', 'config.ini',
        'private.pem', 'credentials.json', 'connection.json', 'credentials.local.json',
        'editor.php.bak', 'editor.php~', 'tool.ps1', 'tool.sh', 'preview_router.php', 'composer.json',
        'storage/backups/backup-lengkap-website_sekolah-20260501-062752.zip',
        'storage/backups/backup-website_sekolah-20260501-062752.sql',
        'storage/backups/.temporary/backup-' . str_repeat('a', 48) . '.zip',
        'storage/backups/final-20260501-062752/manifest.json',
        'storage/backups/final-20260501-062752/database/backup-website_sekolah-20260501-062752.sql',
        'storage/backups/final-20260501-062752/files/public/uploads/gallery/historical.jpg',
        'public/backup-website_sekolah-20260501-062752.sql',
        'public/backup-lengkap-website_sekolah-20260501-062752.zip'];
    foreach ($sensitive as $path) {
        $write($site . '/' . $path, $sentinel);
    }
    // Explicit local fixture config; the database boundary remains null/no-network.
    $write($site . '/.env', '# ' . $sentinel . "\nAPP_ENV=local\nAPP_URL=/website-sekolah\n"
        . "DB_HOST=127.0.0.1\nDB_PORT=33317\nDB_DATABASE=synthetic_test\nDB_USERNAME=synthetic_user\nDB_PASSWORD=\nDB_CHARSET=utf8mb4\n");
    $scriptMarker = 'STAGE6_UNEXPECTED_SCRIPT_EXECUTION';
    $scriptNames = ['canary.php', 'canary.PHP', 'canary.php5', 'canary.php8', 'canary.pht', 'canary.phtml',
        'canary.phar', 'canary.phps', 'canary.cgi', 'canary.pl', 'canary.py', 'canary.rb',
        'canary.asp', 'canary.aspx', 'canary.jsp', 'canary.shtml', 'canary.sh', 'canary.ps1',
        'canary.exe', 'canary.bat', 'canary.cmd', 'canary.vbs', 'canary.wsf', 'canary.custom',
        'canary.php.jpg', 'canary.phtml.png', 'canary.phar.webp', 'canary.jpg.php',
        'canary.html', 'canary.svg', 'canary.js'];
    foreach ($scriptNames as $name) {
        $write($site . '/public/uploads/probes/' . $name, '<?php echo "' . $scriptMarker . '";');
    }
    $image = imagecreatetruecolor(640, 480);
    ob_start(); imagejpeg($image); $jpeg = ob_get_clean();
    ob_start(); imagepng($image); $png = ob_get_clean();
    ob_start(); imagewebp($image); $webp = ob_get_clean();
    imagedestroy($image);
    foreach (['static.jpg' => $jpeg, 'static.jpeg' => $jpeg, 'static.png' => $png, 'static.webp' => $webp] as $name => $bytes) {
        $write($site . '/public/uploads/probes/' . $name, $bytes);
    }
    // Safe image/script polyglot canary verifies that inherited image handlers cannot execute it.
    $write($site . '/public/uploads/probes/static-marker.jpg', $jpeg . '<?php echo "' . $scriptMarker . '";');
    $write($site . '/safe-control.php', '<?php header("X-Powered-By: STAGE6_SAFE_VERSION_CANARY"); echo "STAGE6_PHP_CONTROL";');
    $write($site . '/public-information-control.php', '<?php require __DIR__ . "/includes/bootstrap.php";'
        . '$content = require __DIR__ . "/config/site_data.php";'
        . 'require __DIR__ . "/templates/header.php"; require __DIR__ . "/pages/profil.php";'
        . 'require __DIR__ . "/templates/footer.php";');
    $write($site . '/storage/uploads/documents/head-control.pdf', "%PDF-1.4\nSTAGE6_SAFE_DOWNLOAD_FIXTURE\n%%EOF\n");
    // Execute the real download view with a file fixture and a counted repository boundary, never a database.
    $write($site . '/download-head-control.php', '<?php require __DIR__ . "/includes/functions.php";'
        . 'require_http_methods(["GET", "HEAD"]);'
        . 'function public_document_row($id) { return ["id"=>1,"file_path"=>"storage/uploads/documents/head-control.pdf","original_filename"=>"head-control.pdf"]; }'
        . 'function increment_document_download_count($id) { file_put_contents(__DIR__ . "/download-counter.txt", "counted"); }'
        . 'require __DIR__ . "/pages/document_download.php";');
    $write($site . '/safe-handler-control.jpg', '<?php echo "STAGE6_PARENT_IMAGE_HANDLER";');
    $write($site . '/safe-handler-control.custom', '<?php echo "STAGE6_PARENT_CUSTOM_HANDLER";');
    if (in_array('--browser', $argv, true)) {
        $write($site . '/header-control.php', '<?php require __DIR__ . "/includes/bootstrap.php"; $content = require __DIR__ . "/config/site_data.php"; require __DIR__ . "/templates/header.php"; ?>'
            . '<style>#header-test-style { color: rgb(1, 2, 3); }</style><p id="header-test-style">STAGE6_FRAME_CONTENT</p>'
            . '<button id="header-test-button" onclick="window.headerEventWorked = true" style="background-color: rgb(4, 5, 6)">Test</button>'
            . '<script>window.headerInlineWorked = true;</script><?php require __DIR__ . "/templates/footer.php"; ?>');
    }
    $listingDirectories = ['storage', 'storage/uploads', 'storage/uploads/documents', 'storage/backups',
        'storage/backups/.temporary', 'storage/backups/final-20260501-062752',
        'storage/testing', 'public/uploads', 'public/uploads/probes', 'public/uploads/news',
        'backup', 'backups', 'includes', 'config', 'tests', 'logs', 'nested/backups', 'listing-disabled-control'];
    $listingFilename = 'STAGE6_LISTING_PRIVATE_FILENAME.txt';
    foreach ($listingDirectories as $folder) {
        $write($site . '/' . $folder . '/' . $listingFilename, $sentinel);
    }
    // Positive control proves autoindex is available; only this disposable fixture opts in.
    $write($site . '/listing-enabled-control/.htaccess', 'Options +Indexes');
    $write($site . '/listing-enabled-control/' . $listingFilename, $sentinel);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    $assert(is_resource($socket), 'available loopback port');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $normalized = str_replace('\\', '/', $site);
    $private = str_replace('\\', '/', $run);
    $config = 'ServerRoot "C:/xampp/apache"' . "\nListen 127.0.0.1:$port\nServerName 127.0.0.1:$port\n"
        . 'Include "' . str_replace('\\', '/', $root . '/config/apache_security.conf') . '"' . "\n"
        . 'PidFile "' . $private . '/apache.pid"' . "\n" . 'ErrorLog "' . $private . '/apache.log"' . "\nLogLevel warn\n"
        . "LoadModule authz_core_module modules/mod_authz_core.so\nLoadModule authz_host_module modules/mod_authz_host.so\n"
        . "LoadModule dir_module modules/mod_dir.so\nLoadModule autoindex_module modules/mod_autoindex.so\nLoadModule mime_module modules/mod_mime.so\nLoadModule rewrite_module modules/mod_rewrite.so\nLoadModule alias_module modules/mod_alias.so\nLoadModule headers_module modules/mod_headers.so\n"
        . "LoadFile C:/xampp/php/php8ts.dll\nLoadFile C:/xampp/php/libpq.dll\nLoadFile C:/xampp/php/libsqlite3.dll\n"
        . "LoadModule php_module C:/xampp/php/php8apache2_4.dll\nPHPIniDir C:/xampp/php\nTypesConfig C:/xampp/apache/conf/mime.types\nDirectoryIndex index.php\n"
        . 'DocumentRoot "' . $normalized . '"' . "\nAlias /website-sekolah \"$normalized\"\n"
        . "<Directory />\nAllowOverride None\nRequire all denied\n</Directory>\n"
        . '<Directory "' . $normalized . '">' . "\nAllowOverride All\nRequire all granted\nOptions +Indexes\nAddHandler application/x-httpd-php .jpg .custom\n</Directory>\n"
        . "<FilesMatch \"\\.php$\">\nSetHandler application/x-httpd-php\n</FilesMatch>\n"
        . "<FilesMatch \"\\.phps$\">\nSetHandler application/x-httpd-php-source\n</FilesMatch>\n";
    $conf = $run . '/apache.conf';
    $write($conf, $config);
    $syntax = proc_open([$apache, '-t', '-f', $conf], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $run, null, ['bypass_shell' => true]);
    $assert(is_resource($syntax), 'Apache config syntax probe started');
    fclose($pipes[0]);
    $syntaxOut = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $assert(proc_close($syntax) === 0 && str_contains($syntaxOut, 'Syntax OK'), 'Apache config syntax');
    $process = proc_open([$apache, '-X', '-f', $conf], [0 => ['pipe', 'r'], 1 => ['file', $run . '/stdout.log', 'a'], 2 => ['file', $run . '/stderr.log', 'a']], $pipes, $run, null, ['bypass_shell' => true]);
    $assert(is_resource($process), 'isolated Apache started');
    fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 100; $i++) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
        if (is_resource($socket)) { fclose($socket); $ready = true; break; }
        if (!proc_get_status($process)['running']) { break; }
        usleep(100000);
    }
    $assert($ready, 'Apache listener ready');
    $http = static function (string $path, string $method = 'GET') use ($port, $assert): array {
        $curl = curl_init('http://127.0.0.1:' . $port . $path);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PATH_AS_IS => true]);
        if ($method === 'POST') { curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => 'id=1']); }
        elseif ($method === 'HEAD') { curl_setopt($curl, CURLOPT_NOBODY, true); }
        elseif ($method !== 'GET') { curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method); }
        $raw = curl_exec($curl);
        $result = ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
            'body' => is_string($raw) ? substr($raw, curl_getinfo($curl, CURLINFO_HEADER_SIZE)) : '', 'raw' => $raw];
        curl_close($curl);
        if (!is_string($raw)) { throw new RuntimeException('Local HTTP transport failed'); }
        $headerBlock = substr($raw, 0, strpos($raw, "\r\n\r\n"));
        $assert(!preg_match('/^X-Powered-By:/mi', $headerBlock), 'no PHP/library banner on any Apache response');
        $assert(preg_match('/^Server:\s*Apache\s*$/mi', $headerBlock) === 1,
            'minimal Apache banner without OS/module/version');
        $assert(!preg_match('~<address>.*(?:Apache/|Server at|Port [0-9])~is', $result['body']),
            'no Apache error-page signature');
        return $result;
    };
    if (in_array('--browser', $argv, true)) {
        $write($site . '/frame-control.php', '<?php require __DIR__ . "/includes/bootstrap.php"; ?>'
            . '<iframe src="http://127.0.0.1:' . $port . '/header-control.php"></iframe>');
    }
    $headerChecks = static function (array $response) use ($assert): void {
        $headerBlock = substr($response['raw'], 0, strpos($response['raw'], "\r\n\r\n"));
        foreach (['X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Frame-Options' => 'SAMEORIGIN', 'Content-Security-Policy' => "frame-ancestors 'self'",
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()'] as $name => $value) {
            preg_match_all('/^' . preg_quote($name, '/') . ':\s*([^\r\n]+)/mi', $headerBlock, $matches);
            $assert(count($matches[1]) === 1 && trim($matches[1][0]) === $value, 'one consistent ' . $name);
        }
        $assert(!preg_match('/^Strict-Transport-Security:/mi', $headerBlock), 'no HSTS on local HTTP');
    };
    $publicExposureChecks = static function (array $response) use ($assert, $root, $site): void {
        foreach ([$root, str_replace('\\', '/', $root), $site, str_replace('\\', '/', $site),
            'C:\\xampp', 'C:/xampp', 'LENOVO', 'mysql:host=', 'SQLSTATE[', 'Stack trace',
            'Fatal error:', 'Warning:', 'STAGE6_', 'TEST_TAHAP', 'SMOKE-', 'root@'] as $private) {
            $assert(!str_contains($response['raw'], $private), 'no public path/debug/test marker: ' . $private);
        }
        $assert(!preg_match('~(?:PHP|Apache|MariaDB|MySQL)/[0-9]|phpinfo\(|var_dump\(|print_r\(~i', $response['raw']),
            'no public engine/database/debug banner');
    };
    foreach (['', '/website-sekolah'] as $mount) {
        $publicPage = $http($mount . '/public-information-control.php');
        $assert($publicPage['status'] === 200 && str_contains($publicPage['body'], 'Profil Resmi')
            && str_contains($publicPage['body'], '</html>'), 'real public profile/header/footer rendered without DB');
        $publicExposureChecks($publicPage);
        if (is_file($site . '/download-counter.txt')) { unlink($site . '/download-counter.txt'); }
        $headDownload = $http($mount . '/download-head-control.php?id=1', 'HEAD');
        $assert($headDownload['status'] === 200 && $headDownload['body'] === ''
            && !is_file($site . '/download-counter.txt'), 'HEAD real download handler does not increment counter');
        $getDownload = $http($mount . '/download-head-control.php?id=1');
        $assert($getDownload['status'] === 200 && $getDownload['body'] === file_get_contents($site . '/storage/uploads/documents/head-control.pdf')
            && file_get_contents($site . '/download-counter.txt') === 'counted', 'GET real download still streams file and counts');
        foreach (['/', '/profil', '/ppdb', '/dokumen/unduh?id=1', '/kontak', '/admin/login.php', '/admin/news.php',
            '/admin/admins.php', '/admin/db_backup.php', '/admin/logout.php'] as $route) {
            foreach (['PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'] as $method) {
                $response = $http($mount . $route, $method);
                $assert($method === 'TRACE' ? $response['status'] === 405 :
                    ($response['status'] === 405 && preg_match('/^Allow:\s*GET, HEAD, POST\s*$/mi', $response['raw']) === 1),
                    'unsupported application method denied before database: ' . $method . ' ' . $route);
            }
        }
        foreach (['/', '/profil', '/ppdb', '/dokumen/unduh?id=1'] as $route) {
            $response = $http($mount . $route, 'POST');
            $assert($response['status'] === 405 && preg_match('/^Allow:\s*GET, HEAD\s*$/mi', $response['raw']) === 1,
                'read-only public route rejects POST: ' . $route);
        }
        foreach (['/admin/login.php' => 200, '/admin/news.php?delete_id=1' => 302,
            '/admin/logout.php' => 302, '/profil' => 503, '/ppdb' => 503] as $route => $status) {
            $response = $http($mount . $route, 'HEAD');
            $assert($response['status'] === $status && $response['body'] === '', 'HEAD status/no body: ' . $route);
        }
        $control = $http($mount . '/safe-control.php');
        $assert($control['status'] === 200 && $control['body'] === 'STAGE6_PHP_CONTROL', 'active PHP handler ' . $mount);
        $headerChecks($control);
        foreach (['jpg' => 'STAGE6_PARENT_IMAGE_HANDLER', 'custom' => 'STAGE6_PARENT_CUSTOM_HANDLER'] as $extension => $expected) {
            $handler = $http($mount . '/safe-handler-control.' . $extension);
            $assert($handler['status'] === 200 && $handler['body'] === $expected, 'dangerous inherited handler positive control ' . $extension);
        }
        $headerChecks($http($mount . '/config/database.php'));
        $listingControl = $http($mount . '/listing-enabled-control/');
        $assert($listingControl['status'] === 200 && str_contains($listingControl['body'], $listingFilename), 'autoindex positive control active');
        foreach ($listingDirectories as $folder) {
            foreach (['/', '/?C=N;O=D'] as $suffix) {
                $response = $http($mount . '/' . $folder . $suffix);
                $assert($response['status'] === 403, 'directory listing denied: ' . $mount . '/' . $folder . $suffix);
                $assert(!str_contains($response['body'], $listingFilename) && !str_contains($response['body'], $sentinel)
                    && !str_contains($response['body'], 'Index of '), 'no directory names or private contents disclosed');
            }
        }
        $paths = array_merge($sensitive, ['config/database.php', 'includes/auth.php', 'templates/header.php',
            'pages/maintenance.php', 'pages/berita.php', 'pages/document_download.php',
            '.htaccess', '.user.ini', '.git/HEAD/extra', 'CONFIG/database.php', 'STORAGE/private.txt',
            '%2egit/HEAD', '%63onfig/database.php', 'public/../config/database.php',
            'public/%2e%2e/storage/private.txt', 'public/..%5cstorage%5cprivate.txt']);
        foreach ($paths as $path) {
            foreach (['GET', 'POST'] as $method) {
                $response = $http($mount . '/' . $path, $method);
                $assert(in_array($response['status'], [400, 403, 404], true), $method . ' private path denied: ' . $mount . '/' . $path);
                $assert(!str_contains($response['raw'], $sentinel) && !str_contains($response['raw'], '<?php'), 'no private file bytes');
                $assert(!preg_match('/^Content-Disposition:\s*attachment\b/mi', $response['raw']), 'no sensitive file attachment response');
                $sensitiveObservations[] = ['method' => $method, 'path' => $mount . '/' . $path,
                    'status' => $response['status'], 'private_bytes_disclosed' => false, 'attachment' => false];
            }
        }
        foreach (array_merge($scriptNames, ['canary.php/extra', 'canary.phtml/extra', 'canary.phar/extra']) as $name) {
            foreach (['GET', 'POST'] as $method) {
                $response = $http($mount . '/public/uploads/probes/' . $name, $method);
                $assert(in_array($response['status'], [403, 404], true), 'upload script denied');
                $assert(!str_contains($response['raw'], $scriptMarker), 'upload neither executed nor disclosed');
            }
        }
        foreach (['public/assets/css/style.css', 'public/logo-sekolah.png', 'public/uploads/probes/static.jpg',
            'public/uploads/probes/static.jpeg', 'public/uploads/probes/static.png',
            'public/uploads/probes/static.webp', 'public/uploads/probes/static-marker.jpg'] as $asset) {
            $response = $http($mount . '/' . $asset);
            $assert($response['status'] === 200 && hash('sha256', $response['body']) === hash_file('sha256', $site . '/' . $asset), 'static asset unchanged');
            $headerChecks($response);
        }
        $assert($http($mount . '/public/uploads/probes/')['status'] === 403, 'directory listing denied');
        foreach (['/', '/profil', '/berita', '/ppdb', '/dokumen', '/robots.txt', '/sitemap.xml', '/berita/normal-slug'] as $route) {
            $response = $http($mount . $route);
            $assert($response['status'] === 503 && $response['body'] === 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.', 'real front controller reached without DB: ' . $route);
            $headerChecks($response);
        }
        $login = $http($mount . '/admin/login.php');
        $assert($login['status'] === 200 && str_contains($login['body'], 'name="csrf_token"'), 'real login page and CSRF rendered');
        $headerChecks($login);
        $publicExposureChecks($login);
        $assert($http($mount . '/ppdb', 'POST')['status'] === 405, 'passive PPDB rejects mutation');
        $redirect = $http($mount . '/admin/dashboard.php');
        $assert($redirect['status'] === 302, 'admin auth redirect retained');
        $headerChecks($redirect);
        foreach (['GET', 'POST'] as $method) {
            $diagnostic = $http($mount . '/admin/production_check.php', $method);
            $assert($diagnostic['status'] === 302 && !str_contains($diagnostic['body'], 'APP_URL saat ini'), 'anonymous diagnostic denied');
            $backup = $http($mount . '/admin/db_backup.php?backup_type=full&filename=../../storage/backups/archive.zip', $method);
            $assert($backup['status'] === 302 && preg_match('/^Location:.*admin\/login\.php\s*$/mi', $backup['raw']) === 1,
                'anonymous backup request redirects to login');
            $assert(!preg_match('/^Content-Disposition:\s*attachment\b/mi', $backup['raw'])
                && !str_contains($backup['body'], 'CREATE TABLE') && !str_contains($backup['body'], $sentinel),
                'anonymous backup request does not return archive or SQL');
        }
        foreach (['admin/ppdb_applications.php', 'admin/ppdb_export.php', 'admin/ppdb_download.php', 'ppdb/bukti', 'pages/ppdb_receipt.php'] as $endpoint) {
            foreach (['GET', 'POST', 'HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
                $response = $http($mount . '/' . $endpoint, $method);
                $expectedStatus = $endpoint === 'ppdb/bukti'
                    && !in_array($method, ['GET', 'HEAD', 'POST'], true) ? 405 : 410;
                $assert($response['status'] === $expectedStatus,
                    'retired PPDB tombstone retained: ' . $method . ' ' . $endpoint . ' status=' . $response['status']);
                if ($method === 'HEAD') { $assert($response['body'] === '', 'HEAD tombstone has no body'); }
                $headerChecks($response);
            }
        }
    }
    if (in_array('--browser', $argv, true)) {
        $browser = proc_open(['node', $root . '/tests/stage6_security_headers_browser_test.cjs', (string) $port],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, null, ['bypass_shell' => true]);
        $assert(is_resource($browser), 'browser probe started');
        fclose($pipes[0]);
        $browserOut = stream_get_contents($pipes[1]); $browserErr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $assert(proc_close($browser) === 0, 'browser compatibility probe (inspect stderr if blocked): ' . $browserErr);
        echo trim($browserOut) . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.2);
        $checks++;
        if (is_resource($socket)) {
            fclose($socket);
            fwrite(STDERR, 'Owned Apache listener still active after termination.' . PHP_EOL);
            $exitCode = 1;
        }
    }
    // Remove only this newly created fixture tree, never repository uploads or historical state.
    if ($run && is_dir($run) && str_starts_with(realpath($run), realpath($root . '/storage/testing') . DIRECTORY_SEPARATOR) && !is_link($run)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($run, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
        }
        rmdir($run);
    }
}
if ($exitCode === 0) {
    if (in_array('--sensitive-report', $argv, true)) {
        $evidence = $root . '/storage/testing/stage6-sensitive-http-' . bin2hex(random_bytes(8)) . '.json';
        $write($evidence, json_encode(['passed' => true, 'apache_checks' => $checks,
            'database_used' => false, 'sensitive_requests' => $sensitiveObservations], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'Sensitive HTTP observations: ' . count($sensitiveObservations) . '; private evidence: ' . basename($evidence) . PHP_EOL;
    }
    echo 'PASS: ' . $checks . ' actual Apache security/routing checks; no database connection.' . PHP_EOL;
}
exit($exitCode);
