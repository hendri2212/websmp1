<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__) . '/storage/testing/stage3-mariadb';
$installer = 'C:/xampp/mysql/bin/mysql_install_db.exe';
$database = 'website_sekolah_test_stage3';
$username = 'school_stage3_test';
$port = 33317;
$mode = $argv[1] ?? '';

try {
    if ($mode === '--prepare') {
        if (file_exists($root) || !is_file($installer)) {
            throw new RuntimeException('Refusing initialization: directory exists or installer missing.');
        }
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 1);
        if (is_resource($socket)) {
            fclose($socket);
            throw new RuntimeException('Refusing initialization: testing port already occupied.');
        }
        if (!mkdir($root . '/data', 0700, true) || !mkdir($root . '/exports', 0700)) {
            throw new RuntimeException('Cannot create private testing directories.');
        }
        $manifest = [
            'host' => '127.0.0.1', 'port' => $port, 'database' => $database,
            'username' => $username, 'password' => bin2hex(random_bytes(32)),
            'datadir' => str_replace('\\', '/', (string) realpath($root . '/data')),
            'marker' => bin2hex(random_bytes(32)),
        ];
        $admin = ['username' => 'root', 'password' => bin2hex(random_bytes(32))];
        foreach (['connection.json' => $manifest, 'bootstrap-admin.json' => $admin] as $name => $data) {
            if (file_put_contents($root . '/' . $name, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Cannot store private testing configuration.');
            }
        }
        $pipes = [];
        $process = proc_open([$installer, '--datadir=' . $manifest['datadir'], '--port=' . $port, '--password=' . $admin['password']], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start isolated database initializer.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $result = proc_close($process);
        file_put_contents($root . '/initialization.log', str_replace([$admin['password'], $manifest['password']], '[REDACTED]', $output));
        if ($result !== 0) {
            throw new RuntimeException('Initialization failed; inspect private initialization log.');
        }
        $config = "[mysqld]\nbasedir=C:/xampp/mysql\ndatadir=\"{$manifest['datadir']}\"\nport=$port\nbind-address=127.0.0.1\nskip-name-resolve\nlocal-infile=0\nmax-connections=20\ninnodb-buffer-pool-size=64M\n";
        $config .= 'log-error="' . str_replace('\\', '/', $root . '/server-error.log') . '"' . PHP_EOL;
        $config .= 'pid-file="' . str_replace('\\', '/', $root . '/server.pid') . '"' . PHP_EOL;
        $config .= 'secure-file-priv="' . str_replace('\\', '/', $root . '/exports') . '"' . PHP_EOL;
        if (file_put_contents($root . '/server.ini', $config) === false) {
            throw new RuntimeException('Cannot store isolated server configuration.');
        }
        echo "Prepared isolated data directory. Application schema not imported.\n";
    } elseif ($mode === '--provision') {
        if (is_file($root . '/provisioned.json')) {
            throw new RuntimeException('Refusing repeat provisioning; use read-only preflight.');
        }
        $manifest = json_decode((string) file_get_contents($root . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
        $admin = json_decode((string) file_get_contents($root . '/bootstrap-admin.json'), true, 512, JSON_THROW_ON_ERROR);
        if ($manifest['host'] !== '127.0.0.1' || $manifest['port'] !== $port || $manifest['database'] !== $database || $manifest['username'] !== $username) {
            throw new RuntimeException('Refusing unexpected testing configuration.');
        }
        // No application config/bootstrap is loaded; verify the actual instance before any SQL mutation.
        $pdo = new PDO('mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4', $admin['username'], $admin['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server = $pdo->query('SELECT @@port AS port, @@datadir AS datadir')->fetch(PDO::FETCH_ASSOC);
        if ((int) $server['port'] !== $port || realpath($server['datadir']) !== realpath($manifest['datadir']) || realpath($manifest['datadir']) !== realpath($root . '/data')) {
            throw new RuntimeException('Refusing mutation: actual instance does not match private testing directory.');
        }
        $existing = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name');
        $existing->execute(['name' => $database]);
        if ((int) $existing->fetchColumn() !== 0) {
            throw new RuntimeException('Refusing provisioning into an existing database.');
        }
        $pdo->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('CREATE USER ' . $pdo->quote($username) . "@'127.0.0.1' IDENTIFIED BY " . $pdo->quote($manifest['password']));
        $pdo->exec('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON `' . $database . '`.* TO ' . $pdo->quote($username) . "@'127.0.0.1'");
        $pdo->exec('CREATE TABLE `' . $database . '`.`stage3_test_environment` (id TINYINT PRIMARY KEY, marker CHAR(64) NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO `' . $database . '`.`stage3_test_environment` (id, marker) VALUES (1, :marker)');
        $statement->execute(['marker' => $manifest['marker']]);
        file_put_contents($root . '/provisioned.json', json_encode(['database' => $database, 'created_at' => gmdate('c')], JSON_THROW_ON_ERROR));
        echo "Provisioned testing database and restricted account. No application schema or seed imported.\n";
    } else {
        throw new RuntimeException('Use --prepare or --provision.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: Isolated database setup stopped (' . get_class($exception) . '). No fallback connection attempted.' . PHP_EOL);
    exit(1);
}
