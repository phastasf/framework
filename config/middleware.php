<?php

declare(strict_types=1);

return [
    // Core middleware (required for framework to work)
    // These are always loaded first, in order
    \Phast\Middleware\ErrorHandlerMiddleware::class,
    \Phast\Middleware\SessionMiddleware::class,
    // Add AuthMiddleware here if you want authentication
    // Note: AuthMiddleware requires EncoderInterface to be registered (via AuthProvider)
    // \Phast\Middleware\AuthMiddleware::class,
    \Phast\Middleware\RoutingMiddleware::class,
    \Phast\Middleware\DispatcherMiddleware::class,
    // Add your custom middleware here
    // Example: \App\Middleware\CustomMiddleware::class,
];
