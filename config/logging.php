<?php

declare(strict_types=1);

return [
    // Backend configuration
    // Options: 'stdio', 'file', 'daily', or array of multiple backends
    'backend' => env('LOG_BACKEND', 'file'),

    // File backend configuration
    'file' => [
        'path' => BASE_PATH.'/storage/logs/app.log',
    ],

    // Daily file backend configuration
    'daily' => [
        'path' => BASE_PATH.'/storage/logs/app',
    ],

    // Stdio backend configuration
    'stdio' => [
        'stream' => env('LOG_STDIO_STREAM', 'stdout'), // 'stdout' or 'stderr'
    ],

    // Formatter (optional)
    // Options: 'simple' (default) or 'json'
    'formatter' => env('LOG_FORMATTER', 'simple'),
];
