<?php

declare(strict_types=1);

return [
    // Default queue driver: 'redis', 'sqs', 'elasticmq'
    'driver' => env('QUEUE_DRIVER', 'redis'),

    // Default queue name
    'queue' => env('QUEUE_NAME', 'default'),

    // Redis queue configuration
    'redis' => [
        // Connection string (takes precedence if provided)
        'connection' => env('REDIS_CONNECTION'),

        // Or individual connection parameters
        'scheme' => env('REDIS_SCHEME', 'tcp'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD'),
        'database' => (int) env('REDIS_DATABASE', 0),
    ],

    // ElasticMQ / AWS SQS queue configuration
    'sqs' => [
        'version' => env('AWS_SQS_VERSION', 'latest'),
        'region' => env('AWS_REGION', 'us-east-1'),
        'endpoint' => env('AWS_SQS_ENDPOINT'), // For ElasticMQ (local development)
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    // Alias for SQS (ElasticMQ)
    'elasticmq' => [
        'version' => env('AWS_SQS_VERSION', 'latest'),
        'region' => env('AWS_REGION', 'us-east-1'),
        'endpoint' => env('AWS_SQS_ENDPOINT', 'http://localhost:9324'),
        'key' => env('AWS_ACCESS_KEY_ID', 'x'),
        'secret' => env('AWS_SECRET_ACCESS_KEY', 'x'),
    ],
];
