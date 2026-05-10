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
    $env = load_env_file();
    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    $serverValue = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $serverValue !== false && $serverValue !== null ? $serverValue : $default;
}
