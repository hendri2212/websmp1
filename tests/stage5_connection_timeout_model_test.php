<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

// Constructor injection only: no network connection or application credential is read.
final class Stage5TimeoutPDO extends PDO
{
    public static int $attempts = 0;
    public static mixed $requestedTimeout = null;
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        self::$attempts++;
        self::$requestedTimeout = $options[PDO::ATTR_TIMEOUT] ?? null;
        throw new PDOException('Synthetic connection timeout; SENTINEL_PRIVATE_DSN SENTINEL_PASSWORD');
    }
}

try {
    $source = file_get_contents(__DIR__ . '/../includes/database.php');
    $configExpression = "require __DIR__ . '/../config/database.php'";
    if (substr_count($source, $configExpression) !== 1 || substr_count($source, 'new PDO(') !== 1) {
        throw new LogicException('Connection helper changed; review test injection');
    }
    $source = str_replace([$configExpression, 'new PDO('],
        ["['host'=>'127.0.0.1','port'=>33317,'database'=>'synthetic_testing','charset'=>'utf8mb4','username'=>'synthetic_user','password'=>'SENTINEL_PASSWORD']", 'new Stage5TimeoutPDO('], $source);
    $tokens = token_get_all($source);
    array_shift($tokens);
    $source = implode('', array_map(static fn($token) => is_array($token) ? $token[1] : $token, $tokens));
    ob_start();
    eval($source);
    if (db() !== null || db_available() !== false || db() !== null || Stage5TimeoutPDO::$attempts !== 1
        || Stage5TimeoutPDO::$requestedTimeout !== 5) {
        throw new LogicException('Timeout did not fail closed or failed connection was retried implicitly');
    }
    $output = ob_get_clean();
    if ($output !== '') { throw new LogicException('Connection failure disclosed output'); }
    echo "PASS: Timeout exception caught, null/unavailable cached, zero network calls, zero response disclosure.\n";
} catch (Throwable $exception) {
    if (ob_get_level()) { ob_end_clean(); }
    fwrite(STDERR, 'FAIL: Timeout model (' . get_class($exception) . ').' . PHP_EOL);
    exit(1);
}
