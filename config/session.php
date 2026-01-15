<?php

declare(strict_types=1);

return [
    'cookie' => [
        // Session cookie name
        'name' => env('SESSION_COOKIE', 'PHPSESSID'),

        // Cookie lifetime in seconds (0 = until browser closes)
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),

        // Cookie path
        'path' => env('SESSION_PATH', '/'),

        // Cookie domain (null = current domain)
        'domain' => env('SESSION_DOMAIN', null),

        // Only send cookie over HTTPS
        'secure' => (bool) env('SESSION_SECURE', false),

        // Only accessible via HTTP (not JavaScript)
        'httponly' => (bool) env('SESSION_HTTPONLY', true),

        // SameSite attribute: 'Strict', 'Lax', or 'None'
        'samesite' => env('SESSION_SAMESITE', 'Lax'),
    ],
];
