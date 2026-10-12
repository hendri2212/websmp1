<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !(PHP_SAPI === 'cli-server' && defined('STAGE3_ISOLATED_HTTP') && STAGE3_ISOLATED_HTTP === true)) {
    http_response_code(403);
    exit('CLI only');
}

function stage3_test_database(): PDO
{
    $root = dirname(__DIR__) . '/storage/testing/stage3-mariadb';
    $config = json_decode((string) file_get_contents($root . '/connection.json'), true, 512, JSON_THROW_ON_ERROR);
    if ($config['host'] !== '127.0.0.1' || $config['port'] !== 33317
        || $config['database'] !== 'website_sekolah_test_stage3' || $config['username'] !== 'school_stage3_test'
        || !is_file($root . '/provisioned.json') || !is_string($config['password']) || strlen($config['password']) < 32
        || realpath($config['datadir']) !== realpath($root . '/data')) {
        throw new RuntimeException('Unverified testing configuration.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=33317;dbname=website_sekolah_test_stage3;charset=utf8mb4', $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $server = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@port AS port, @@datadir AS datadir')->fetch();
    if ($server['db'] !== $config['database'] || $server['account'] !== 'school_stage3_test@127.0.0.1'
        || (int) $server['port'] !== 33317 || realpath($server['datadir']) !== realpath($root . '/data')) {
        throw new RuntimeException('Actual connection does not match isolated testing instance.');
    }
    $marker = $pdo->query('SELECT marker FROM stage3_test_environment WHERE id = 1')->fetchColumn();
    if (!is_string($marker) || !hash_equals($config['marker'], $marker)) {
        throw new RuntimeException('Testing marker mismatch.');
    }
    foreach ($pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN) as $grant) {
        if (!str_starts_with($grant, 'GRANT USAGE ON *.* TO ')
            && !str_contains($grant, ' ON `website_sekolah_test_stage3`.* TO ')) {
            throw new RuntimeException('Testing account has unexpected privileges.');
        }
    }
    return $pdo;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $pdo = stage3_test_database();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "PASS: Isolated testing connection, instance directory, restricted account, grants and marker verified.\n";
        echo 'Database: website_sekolah_test_stage3; host: 127.0.0.1; port: 33317; tables: ' . count($tables) . PHP_EOL;
        echo 'Application schema imported: ' . ($tables === ['stage3_test_environment'] ? 'No' : 'Review required') . PHP_EOL;
    } catch (Throwable $exception) {
        fwrite(STDERR, 'FAIL: Testing database not verified (' . get_class($exception) . '). No fallback attempted.' . PHP_EOL);
        exit(1);
    }
}
