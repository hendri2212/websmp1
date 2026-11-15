<?php

require_once __DIR__ . '/paths.php';

function db(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo;
    }

    try {
        $config = require APP_ROOT . '/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    } catch (Throwable $exception) {
        $pdo = null;
    }

    return $pdo;
}

function db_available(): bool
{
    return db() instanceof PDO;
}
