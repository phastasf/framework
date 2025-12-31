<?php

declare(strict_types=1);

return [
    'base_path' => BASE_PATH,
    'debug' => (bool) env('APP_DEBUG', true),

    'controllers' => [
        'namespace' => 'App\\Controllers',
    ],
];
