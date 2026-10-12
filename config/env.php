<?php

function load_env_file(): array
{
    static $values;
    if ($values !== null) {
        return $values;
    }

    $values = [];
    $path = __DIR__ . '/../.env';
    if (!is_file($path)) {
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        $values[$key] = $value;
    }

    return $values;
}

function env_value(string $key, mixed $default = null): mixed
{
    $serverValue = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($serverValue !== false && $serverValue !== null) {
        return $serverValue;
    }
    $env = load_env_file();
    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    return $default;
}

function configuration_error(): void
{
    throw new RuntimeException('Konfigurasi sistem tidak tersedia. Hubungi pengelola sistem.');
}

function configuration_string(string $key, bool $allowEmpty = false): string
{
    $value = env_value($key);
    if (!is_string($value) || (!$allowEmpty && trim($value) === '') || str_contains($value, "\0")) {
        configuration_error();
    }
    return $value;
}

function configuration_environment(): string
{
    $environment = configuration_string('APP_ENV');
    if (!in_array($environment, ['local', 'production'], true)) {
        configuration_error();
    }
    return $environment;
}

function configuration_url(string $environment): string
{
    $url = configuration_string('APP_URL');
    if (preg_match('/[\x00-\x20\x7f\\\\<>"\']/', $url) || str_contains($url, '?') || str_contains($url, '#')) {
        configuration_error();
    }
    if ($environment === 'local' && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        return $url;
    }
    $parts = parse_url($url);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts)
        || isset($parts['user']) || isset($parts['pass'])
        || !in_array($parts['scheme'] ?? '', $environment === 'production' ? ['https'] : ['http', 'https'], true)) {
        configuration_error();
    }
    if ($environment === 'production' && configuration_loopback($parts['host'] ?? '')) {
        configuration_error();
    }
    return $url;
}

function configuration_loopback(string $host): bool
{
    $host = strtolower(rtrim(trim($host, '[]'), '.'));
    $address = @inet_pton($host);
    $mappedLoopback = is_string($address) && strlen($address) === 16
        && substr($address, 0, 12) === str_repeat("\0", 10) . "\xff\xff"
        && ord($address[12]) === 127;
    return $mappedLoopback || $host === 'localhost' || str_ends_with($host, '.localhost') || $host === '::1' || $host === '::'
        || str_starts_with($host, '127.') || $host === '0.0.0.0'
        || preg_match('/\A(?:[0-9]+|0x[0-9a-f]+)\z/', $host) === 1;
}
