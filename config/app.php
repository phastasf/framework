<?php

declare(strict_types=1);

return [
    'base_path' => BASE_PATH,
    'debug' => (bool) env('APP_DEBUG', false),

    'controllers' => [
        'namespace' => 'App\\Controllers',
        'path' => BASE_PATH.'/app/Controllers',
    ],

    'jobs' => [
        'namespace' => 'App\\Jobs',
        'path' => BASE_PATH.'/app/Jobs',
    ],

    'models' => [
        'namespace' => 'App\\Models',
        'path' => BASE_PATH.'/app/Models',
    ],

    'commands' => [
        'namespace' => 'App\\Commands',
        'path' => BASE_PATH.'/app/Commands',
    ],

    'events' => [
        'namespace' => 'App\\Events',
        'path' => BASE_PATH.'/app/Events',
    ],

    'providers' => [
        'namespace' => 'App\\Providers',
        'path' => BASE_PATH.'/app/Providers',
    ],

    'routes' => [
        'web' => BASE_PATH.'/routes/web.php',
    ],

    'public' => [
        'path' => BASE_PATH.'/public',
        'index' => BASE_PATH.'/public/index.php',
    ],
];
