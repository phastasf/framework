<?php

declare(strict_types=1);

return [
    // ConfigProvider must be first (other providers depend on it)
    \Phast\Providers\ConfigProvider::class,
    \Phast\Providers\CacheProvider::class,
    \Phast\Providers\DatabaseProvider::class,
    \Phast\Providers\MigrationProvider::class,
    \Phast\Providers\LoggingProvider::class,
    \Phast\Providers\RouterProvider::class,
    \Phast\Providers\AuthProvider::class,
    \Phast\Providers\ViewProvider::class,
    \Phast\Providers\SessionProvider::class,
    \Phast\Providers\CaptchaProvider::class,
    \Phast\Providers\QueueProvider::class,
    \Phast\Providers\MailProvider::class,
    \Phast\Providers\ClockProvider::class,
    \Phast\Providers\HttpMessageProvider::class,
    \Phast\Providers\EventProvider::class,
    \Phast\Providers\HttpProvider::class,
];
