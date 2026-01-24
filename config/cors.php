<?php

declare(strict_types=1);

return [
    // Allowed origins (use '*' for all origins, or array of specific origins)
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*'),

    // Allowed HTTP methods
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    // Allowed request headers (use '*' for all headers, or array of specific headers)
    'allowed_headers' => env('CORS_ALLOWED_HEADERS', '*'),

    // Headers that can be exposed to the client
    'exposed_headers' => [],

    // Maximum age (in seconds) for preflight requests
    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    // Allow credentials (cookies, authorization headers, etc.)
    'allow_credentials' => (bool) env('CORS_ALLOW_CREDENTIALS', false),

    // Paths/prefixes to include (empty = all paths)
    'include' => [],

    // Paths/prefixes to exclude
    'exclude' => [],
];
