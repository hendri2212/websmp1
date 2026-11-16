<?php

require_once __DIR__ . '/env.php';

$environment = configuration_environment();
return [
    'app_name' => env_value('APP_NAME', 'Website Sekolah'),
    'environment' => $environment,
    'base_url' => configuration_url($environment),
];
