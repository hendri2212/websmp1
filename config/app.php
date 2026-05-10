<?php

require_once __DIR__ . '/env.php';

return [
    'app_name' => env_value('APP_NAME', 'SMP Negeri 1 Kotabaru'),
    'environment' => env_value('APP_ENV', 'local'),
    'base_url' => env_value('APP_URL', '/website-sekolah'),
];
