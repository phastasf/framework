<?php

declare(strict_types=1);

return [
    // Default mail driver: 'smtp', 'mailgun', 'resend'
    'driver' => env('MAIL_DRIVER', 'smtp'),

    // SMTP transport configuration
    'smtp' => [
        'host' => env('MAIL_HOST', 'localhost'),
        'port' => (int) env('MAIL_PORT', 25),
        'encryption' => env('MAIL_ENCRYPTION'), // 'tls', 'ssl', or null
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'timeout' => (int) env('MAIL_TIMEOUT', 30),
    ],

    // Mailgun transport configuration
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'api_key' => env('MAILGUN_API_KEY'),
        'region' => env('MAILGUN_REGION', 'us'), // 'us' or 'eu'
    ],

    // Resend transport configuration
    'resend' => [
        'api_key' => env('RESEND_API_KEY'),
    ],
];
