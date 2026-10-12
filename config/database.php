<?php

require_once __DIR__ . '/env.php';

$environment = configuration_environment();
configuration_url($environment);
$host = configuration_string('DB_HOST');
$port = configuration_string('DB_PORT');
$database = configuration_string('DB_DATABASE');
$username = configuration_string('DB_USERNAME');
$password = configuration_string('DB_PASSWORD', $environment === 'local');
$charset = configuration_string('DB_CHARSET');
if ((!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
    || !preg_match('/\A[1-9][0-9]{0,4}\z/', $port) || (int) $port > 65535
    || !preg_match('/\A[a-zA-Z0-9_]{1,64}\z/', $database)
    || !preg_match('/\A[a-zA-Z0-9_.-]{1,80}\z/', $username)
    || $charset !== 'utf8mb4') {
    configuration_error();
}
if ($environment === 'production' && (configuration_loopback($host) || strtolower($username) === 'root'
    || strtolower($database) === 'website_sekolah'
    || preg_match('/(?:\A|_)(?:dev|development|local|test|testing)(?:_|\z)/i', $database))) {
    configuration_error();
}
return compact('host', 'database', 'username', 'password', 'charset') + ['port' => (int) $port];
