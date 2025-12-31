<?php

declare(strict_types=1);

return [
    // Store driver options: 'memory', 'file', 'redis', 'predis', 'memcache'
    'driver' => env('CACHE_DRIVER', 'file'),

    // File store configuration
    'file' => [
        'path' => BASE_PATH.'/storage/cache',
    ],

    // Redis store configuration (ext-redis)
    'redis' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'port' => (int) env('REDIS_PORT', 6379),
        'database' => (int) env('REDIS_DATABASE', 0),
        'password' => env('REDIS_PASSWORD', null),
    ],

    // Predis store configuration (predis/predis library)
    'predis' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'port' => (int) env('REDIS_PORT', 6379),
        'database' => (int) env('REDIS_DATABASE', 0),
        'password' => env('REDIS_PASSWORD', null),
    ],

    // Memcache store configuration
    'memcache' => [
        'host' => env('MEMCACHE_HOST', 'localhost'),
        'port' => (int) env('MEMCACHE_PORT', 11211),
    ],
];
