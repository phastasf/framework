# Phast Framework

[![Latest Version](https://img.shields.io/packagist/v/phastasf/framework.svg?style=flat-square)](https://packagist.org/packages/phastasf/framework)
[![License](https://img.shields.io/packagist/l/phastasf/framework.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/phastasf/framework.svg?style=flat-square)](https://packagist.org/packages/phastasf/framework)

A lightweight, modern PHP framework for building CLI, web, and API applications. Built on PSR standards with a clean, intuitive API.

## Features

- **PSR Standards**: PSR-7, PSR-11, PSR-15, PSR-3, PSR-6, PSR-16, PSR-20
- **Dependency Injection**: Automatic dependency resolution
- **Routing & Middleware**: Fast routing with PSR-15 middleware pipeline
- **Database & ORM**: Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server) with Datum ORM
- **Migrations**: Schema versioning with Kram
- **Views**: Template engine with layout inheritance
- **Authentication**: JWT-based authentication
- **Queue System**: Job queues with Redis and ElasticMQ/SQS
- **Caching**: Multiple backends (File, Memory, Redis, Predis, Memcache)
- **Logging**: Flexible logging with multiple backends
- **Email**: SMTP, Mailgun, and Resend transports
- **Validation**: Powerful input validation
- **Console Commands**: Built-in CLI commands and generators

## Requirements

- PHP 8.2+
- Composer

## Installation

```bash
composer require phastasf/framework
```

## Quick Start

### Web Application

Create `public/index.php`:

```php
<?php

require __DIR__.'/../vendor/autoload.php';

define('BASE_PATH', __DIR__.'/..');

$framework = new Phast\Framework;

$framework->getWebEntrypoint()->handle();
```

### Console Application

Create `console`:

```php
#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

define('BASE_PATH', __DIR__);

$framework = new Phast\Framework;

exit($framework->getConsoleEntrypoint()->run());
```

```bash
chmod +x console
```

## Configuration

Configuration files are in `config/`. The framework loads defaults from the package and merges your project's `config/` overrides.

### Application

```php
// config/app.php
return [
    'debug' => env('APP_DEBUG', false),
    'controllers' => ['namespace' => 'App\\Controllers'],
    'models' => ['namespace' => 'App\\Models'],
    'jobs' => ['namespace' => 'App\\Jobs'],
];
```

### Database

```php
// config/database.php
return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'migrations' => BASE_PATH.'/database/migrations',
    'mysql' => [
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', ''),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
];
```

### Session

Configure session cookie settings:

```php
// config/session.php
return [
    'cookie' => [
        'name' => env('SESSION_COOKIE', 'PHPSESSID'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'path' => env('SESSION_PATH', '/'),
        'domain' => env('SESSION_DOMAIN', null),
        'secure' => env('SESSION_SECURE', false),
        'httponly' => env('SESSION_HTTPONLY', true),
        'samesite' => env('SESSION_SAMESITE', 'Lax'), // 'Strict', 'Lax', or 'None'
    ],
];
```

### Trusted Proxies

When running behind a reverse proxy or load balancer, configure trusted proxies for accurate client IP detection:

```php
// config/proxies.php
return [
    'trusted' => [
        '10.0.0.0/8',      // Private network
        '172.16.0.0/12',  // Docker network
        '192.168.0.0/16', // Private network
        '127.0.0.1',      // Localhost IPv4
        '::1',            // Localhost IPv6
        // Add your production proxy IPs here
        // '203.0.113.0/24', // Your load balancer IP range
    ],
    'headers' => [
        'Forwarded',
        'X-Forwarded-For',
        'X-Real-Ip',
        'Client-Ip',
    ],
];
```

## Routing

```php
// routes/web.php
return function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->get('/users/{id}', 'UserController@show');
    $router->post('/users', 'UserController@store');
};
```

## Middleware

Middleware is configured in `config/middleware.php`:

```php
// config/middleware.php
return [
    // Core framework middleware (required)
    \Phast\Middleware\ErrorHandlerMiddleware::class,
    \Phast\Middleware\SessionMiddleware::class,
    // Client IP detection middleware (must be before routing if behind proxy)
    // \Phast\Middleware\ClientIpMiddleware::class,
    // Add AuthMiddleware here if you want authentication
    // \Phast\Middleware\AuthMiddleware::class,
    // Add your custom middleware here (before routing)
    \App\Middleware\CustomMiddleware::class,
    \Phast\Middleware\RoutingMiddleware::class,
    \Phast\Middleware\DispatcherMiddleware::class,
];
```

Generate a new middleware:

```bash
php console g:middleware CustomMiddleware
```

### Client IP Detection

When running behind a reverse proxy or load balancer, add `ClientIpMiddleware` to your middleware stack to correctly detect client IP addresses. The middleware reads trusted proxy configuration from `config/proxies.php` and extracts the real client IP from proxy headers.

## Service Providers

Service providers are configured in `config/providers.php`:

```php
// config/providers.php
return [
    // ConfigProvider must be first (other providers depend on it)
    \Phast\Providers\ConfigProvider::class,
    \Phast\Providers\CacheProvider::class,
    \Phast\Providers\DatabaseProvider::class,
    // ... other framework providers
    // Add your custom providers here
    \App\Providers\CustomProvider::class,
];
```

Generate a new service provider:

```bash
php console g:provider CustomProvider
```

Service providers implement `Phast\Providers\ProviderInterface` with two methods:

- `provide(Container $container)`: Register services in the container
- `init(Container $container)`: Initialize services after all providers are registered

```php
namespace App\Providers;

use Katora\Container;
use Phast\Providers\ProviderInterface;

class CustomProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register services here
        $container->set('custom.service', fn() => new CustomService);
    }

    public function init(Container $container): void
    {
        // Initialize services here (called after all providers are registered)
        $service = $container->get('custom.service');
        $service->initialize();
    }
}
```

## Controllers

```php
namespace App\Controllers;

use Phast\Controller;

class HomeController extends Controller
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->render('welcome', ['message' => 'Hello, Phast!']);
    }
}
```

## Models

```php
namespace App\Models;

use Datum\Model;

class User extends Model
{
    protected static ?string $table = 'users';
}

// Usage
$user = User::find(1);
$user = new User;
$user->name = 'John';
$user->save();
```

## Migrations

```php
// Generated migration
class CreateUsersTable implements MigrationInterface
{
    public function up(ConnectionInterface $connection): void
    {
        $connection->execute("CREATE TABLE users (...)");
    }

    public function down(ConnectionInterface $connection): void
    {
        $connection->execute("DROP TABLE users");
    }
}
```

## Queue Jobs

```php
namespace App\Jobs;

use Qatar\Job;

class SendEmailJob extends Job
{
    public function handle(array $payload): void
    {
        // Job logic
    }

    public function retries(): int
    {
        return 3;
    }
}
```

## Console Commands

### Generators

- `g:command` - Generate console command
- `g:controller` - Generate controller
- `g:event` - Generate event class
- `g:job` - Generate job
- `g:middleware` - Generate middleware class
- `g:migration` - Generate migration
- `g:model` - Generate model
- `g:provider` - Generate service provider

### Database

- `m:up` - Run pending migrations
- `m:down [count]` - Rollback migrations (default: 1)

### Development

- `serve` - Start development server
- `worker` - Run queue worker
- `shell` - Start interactive PHP shell (REPL) with container access
- `uncache` - Clear cached config, routes, and application cache

## Usage Examples

### Caching

```php
$cache = $container->get(CacheInterface::class);
$cache->set('key', 'value', 3600);
$value = $cache->get('key');
```

### Logging

```php
$logger = $container->get(LoggerInterface::class);
$logger->info('User logged in', ['user_id' => 123]);
```

### HTTP Client

The framework includes a PSR-18 compliant HTTP client:

```php
use Psr\Http\Client\ClientInterface;

$client = $container->get(ClientInterface::class);
$request = $requestFactory->createRequest('GET', 'https://api.example.com/data');
$response = $client->sendRequest($request);
```

### Validation

```php
use Filtr\Validator;

$validator = new Validator;
$validator->required('email')->email();
$validator->required('password')->min(8);
$result = $validator->validate($data);
```

### JWT Authentication

```php
// config/auth.php
return [
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'algorithm' => 'HS256',
    ],
    'middleware' => [
        'include' => ['/api/*'],
        'exclude' => ['/api/auth/*'],
        'required' => true,
    ],
];
```

## Error Handling

The framework includes centralized error handling that:

- Catches all exceptions
- Returns JSON for API requests (`Accept: application/json`)
- Renders HTML error pages for web requests
- Logs 5xx errors automatically
- Shows debug info when `app.debug` is enabled

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

Built on excellent PSR-compliant libraries:

- [Katora](https://github.com/vaibhavpandeyvpz/katora) - DI Container
- [Kunfig](https://github.com/vaibhavpandeyvpz/kunfig) - Configuration Management
- [Databoss](https://github.com/vaibhavpandeyvpz/databoss) - Database Connections
- [Datum](https://github.com/vaibhavpandeyvpz/datum) - ORM
- [Kram](https://github.com/vaibhavpandeyvpz/kram) - Migrations
- [Tez](https://github.com/vaibhavpandeyvpz/tez) - Routing
- [Vidyut](https://github.com/vaibhavpandeyvpz/vidyut) - Middleware Pipeline
- [Sandesh](https://github.com/vaibhavpandeyvpz/sandesh) - HTTP Messages
- [Phew](https://github.com/vaibhavpandeyvpz/phew) - Templates
- [Filtr](https://github.com/vaibhavpandeyvpz/filtr) - Validation
- [Drishti](https://github.com/vaibhavpandeyvpz/drishti) - Logging
- [Godam](https://github.com/vaibhavpandeyvpz/godam) - Caching
- [Qatar](https://github.com/vaibhavpandeyvpz/qatar) - Queues
- [Jweety](https://github.com/vaibhavpandeyvpz/jweety) - JWT
- [Envelope](https://github.com/vaibhavpandeyvpz/envelope) - Email
- [Phlash](https://github.com/vaibhavpandeyvpz/phlash) - Flash Messages
- [Ank](https://github.com/vaibhavpandeyvpz/ank) - Captcha
- [Soochak](https://github.com/vaibhavpandeyvpz/soochak) - Events
- [Samay](https://github.com/vaibhavpandeyvpz/samay) - Clock
- [Dakiya](https://github.com/vaibhavpandeyvpz/dakiya) - HTTP Client (PSR-18)
- [Clip](https://github.com/vaibhavpandeyvpz/clip) - CLI Commands
- [Prayog](https://github.com/vaibhavpandeyvpz/prayog) - REPL/Shell (dev)
