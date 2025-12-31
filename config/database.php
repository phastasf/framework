<?php

declare(strict_types=1);

return [
    // Driver options: 'mysql', 'pgsql', 'sqlite', 'sqlsrv'
    'driver' => env('DB_DRIVER', 'mysql'),

    // MySQL/MariaDB configuration
    'mysql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => (int) env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'phast'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'prefix' => env('DB_PREFIX', ''),
    ],

    // PostgreSQL configuration
    'pgsql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => (int) env('DB_PORT', 5432),
        'database' => env('DB_DATABASE', 'phast'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8'),
        'prefix' => env('DB_PREFIX', ''),
    ],

    // SQLite configuration
    'sqlite' => [
        'database' => env('DB_DATABASE', BASE_PATH.'/database/database.sqlite'),
    ],

    // SQL Server configuration
    'sqlsrv' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => (int) env('DB_PORT', 1433),
        'database' => env('DB_DATABASE', 'phast'),
        'username' => env('DB_USERNAME', 'sa'),
        'password' => env('DB_PASSWORD', ''),
        'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', false),
    ],

    'migrations' => BASE_PATH.'/database/migrations',
];
