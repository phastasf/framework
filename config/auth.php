<?php

declare(strict_types=1);

return [
    'jwt' => [
        'secret' => env('AUTH_SECRET', 'your-secret-key-change-this-in-production'),
        'algorithm' => env('AUTH_ALGORITHM', 'HS256'),
    ],

    'middleware' => [
        // Whether authentication is required (true) or optional (false)
        'required' => (bool) env('AUTH_REQUIRED', true),

        // Paths/prefixes to include (empty array = all paths)
        // Examples: ['/api/*', '/admin']
        'include' => ['/api/*'],

        // Paths/prefixes to exclude
        // Examples: ['/public', '/login', '/register']
        'exclude' => [],

        // HTTP header name to extract token from
        'header' => env('AUTH_HEADER', 'Authorization'),

        // Token prefix in header
        'prefix' => env('AUTH_PREFIX', 'Bearer'),
    ],
];
