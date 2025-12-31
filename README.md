# Phast Framework

[![Latest Version](https://img.shields.io/packagist/v/phastasf/framework.svg?style=flat-square)](https://packagist.org/packages/phastasf/framework)
[![License](https://img.shields.io/packagist/l/phastasf/framework.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/phastasf/framework.svg?style=flat-square)](https://packagist.org/packages/phastasf/framework)

A lightweight, modern PHP framework for building CLI, web, and API applications. Phast is built on PSR standards and provides a clean, intuitive API for rapid development.

## Features

### 🚀 Core Features

- **PSR Standards**: Full compliance with PSR-7, PSR-11, PSR-15, PSR-3, PSR-6, PSR-16, and PSR-20
- **Dependency Injection**: Built-in service container with automatic dependency resolution
- **Routing**: Fast, flexible routing with parameter binding and middleware support
- **Middleware Pipeline**: PSR-15 compliant middleware for request/response processing
- **Configuration Management**: Flexible configuration system with environment variable support
- **Error Handling**: Centralized exception handling with JSON/HTML response formatting

### 🗄️ Database & ORM

- **Multi-Database Support**: MySQL, PostgreSQL, SQLite, and SQL Server
- **ORM**: Active Record pattern with Datum
- **Migrations**: Database schema versioning with Kram
- **Query Builder**: Fluent query builder with Databoss

### 🎨 Views & Templates

- **Template Engine**: Phew template engine with layout inheritance
- **Multiple View Paths**: Support for namespaced view directories
- **Template Inheritance**: Extend layouts and override blocks

### 🔐 Authentication & Security

- **JWT Authentication**: Token-based authentication with Jweety
- **CAPTCHA**: Text and math-based CAPTCHA generation with Ank
- **Flash Messages**: Session-based flash messages with Phlash
- **Input Validation**: Powerful validation with Filtr

### 📦 Additional Features

- **Caching**: Multiple cache backends (File, Memory, Redis, Predis, Memcache)
- **Logging**: Flexible logging with multiple backends (File, Daily, Stdio)
- **Queue System**: Job queues with Redis and ElasticMQ/SQS support
- **Email**: SMTP, Mailgun, and Resend email transports
- **Sessions**: Session management with configurable drivers
- **Events**: Event-driven architecture with Soochak
- **Console Commands**: Built-in CLI commands and code generators

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

Install via Composer:

```bash
composer require phastasf/framework
```

## Quick Start

### Web Application

Create a `public/index.php` file:

```php
<?php

require __DIR__.'/../vendor/autoload.php';

define('BASE_PATH', __DIR__.'/..');

$framework = new Phast\Framework;
$entrypoint = $framework->getWebEntrypoint();
$entrypoint->handle();
```

### Console Application

Create a `console` file:

```php
#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

define('BASE_PATH', __DIR__);

$framework = new Phast\Framework;
$entrypoint = $framework->getConsoleEntrypoint();
exit($entrypoint->run());
```

Make it executable:

```bash
chmod +x console
```

## Configuration

Configuration files are located in the `config/` directory. The framework loads default configurations from the package and allows you to override them in your project's `config/` directory.

### Application Configuration

```php
// config/app.php
return [
    'debug' => env('APP_DEBUG', false),
    'controllers' => [
        'namespace' => 'App\\Controllers',
    ],
];
```

### Database Configuration

```php
// config/database.php
return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'migrations' => BASE_PATH.'/database/migrations',

    'mysql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', ''),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
];
```

## Routing

Define routes in `routes/web.php`:

```php
<?php

use Tez\Router;

return function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->get('/users/{id}', 'UserController@show');
    $router->post('/users', 'UserController@store');
};
```

## Controllers

Controllers extend the base `Phast\Controller` class:

```php
<?php

namespace App\Controllers;

use Phast\Controller;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends Controller
{
    public function index(ServerRequestInterface $request)
    {
        return $this->render('welcome', [
            'message' => 'Hello, Phast!',
        ]);
    }
}
```

## Middleware

Create custom middleware by implementing `Psr\Http\Server\MiddlewareInterface`:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Your middleware logic here

        return $handler->handle($request);
    }
}
```

## Database & Migrations

### Creating Migrations

```bash
php console g:migration create_users_table
```

### Running Migrations

```bash
php console migrate
```

### Using the ORM

```php
use Datum\Model;

class User extends Model
{
    protected string $table = 'users';
}

// Find a user
$user = User::find(1);

// Create a user
$user = new User;
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();
```

## Queue Jobs

### Creating Jobs

```bash
php console g:job SendEmail
```

### Job Implementation

```php
<?php

namespace App\Jobs;

use Qatar\Job;

class SendEmailJob extends Job
{
    public function handle(array $payload): void
    {
        // Send email logic
        mail($payload['to'], $payload['subject'], $payload['body']);
    }

    public function retries(): int
    {
        return 3; // Retry 3 times on failure
    }
}
```

### Running Workers

```bash
php console worker
```

## Caching

```php
use Psr\SimpleCache\CacheInterface;

// Get cache from container
$cache = $container->get(CacheInterface::class);

// Store a value
$cache->set('key', 'value', 3600); // TTL: 1 hour

// Retrieve a value
$value = $cache->get('key');
```

## Logging

```php
use Psr\Log\LoggerInterface;

// Get logger from container
$logger = $container->get(LoggerInterface::class);

$logger->info('User logged in', ['user_id' => 123]);
$logger->error('Something went wrong', ['exception' => $e]);
```

## Validation

```php
use Filtr\Validator;

$validator = new Validator;
$validator->required('email')->email();
$validator->required('password')->min(8);

$result = $validator->validate($data);

if ($result->isValid()) {
    // Process valid data
} else {
    // Handle errors
    $errors = $result->errors();
}
```

## JWT Authentication

Configure JWT in `config/auth.php`:

```php
return [
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'algorithm' => 'HS256',
    ],
    'middleware' => [
        'include' => ['/api/*'],
        'exclude' => ['/api/auth/*'],
        'required' => true,
        'header' => 'Authorization',
        'prefix' => 'Bearer',
    ],
];
```

## Console Commands

Phast includes several built-in commands:

- `g:controller` - Generate a new controller
- `g:migration` - Generate a new migration
- `g:job` - Generate a new job class
- `serve` - Start the development server
- `worker` - Run queue workers
- `migrate` - Run database migrations

## Service Providers

The framework uses service providers to register services in the container. All providers are automatically registered when you instantiate `Phast\Framework`.

## Error Handling

The framework includes a centralized error handler that:

- Catches all exceptions
- Returns JSON responses for API requests (when `Accept: application/json`)
- Renders HTML error pages for web requests
- Logs 5xx errors automatically
- Shows debug information when `app.debug` is enabled

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

Phast Framework is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Phast Framework is built on top of excellent PSR-compliant libraries:

- [Katora](https://github.com/vaibhavpandeyvpz/katora) - Dependency Injection Container
- [Tez](https://github.com/vaibhavpandeyvpz/tez) - Routing
- [Vidyut](https://github.com/vaibhavpandeyvpz/vidyut) - Middleware Pipeline
- [Sandesh](https://github.com/vaibhavpandeyvpz/sandesh) - HTTP Messages
- [Datum](https://github.com/vaibhavpandeyvpz/datum) - ORM
- [Databoss](https://github.com/vaibhavpandeyvpz/databoss) - Database Abstraction
- [Kram](https://github.com/vaibhavpandeyvpz/kram) - Migrations
- [Phew](https://github.com/vaibhavpandeyvpz/phew) - Template Engine
- [Filtr](https://github.com/vaibhavpandeyvpz/filtr) - Validation
- [Drishti](https://github.com/vaibhavpandeyvpz/drishti) - Logging
- [Godam](https://github.com/vaibhavpandeyvpz/godam) - Caching
- [Qatar](https://github.com/vaibhavpandeyvpz/qatar) - Job Queues
- [Envelope](https://github.com/vaibhavpandeyvpz/envelope) - Email
- [Jweety](https://github.com/vaibhavpandeyvpz/jweety) - JWT
- [Ank](https://github.com/vaibhavpandeyvpz/ank) - CAPTCHA
- [Phlash](https://github.com/vaibhavpandeyvpz/phlash) - Flash Messages
- [Soochak](https://github.com/vaibhavpandeyvpz/soochak) - Events
- [Samay](https://github.com/vaibhavpandeyvpz/samay) - Clock
- [Kunfig](https://github.com/vaibhavpandeyvpz/kunfig) - Configuration
- [Clip](https://github.com/vaibhavpandeyvpz/clip) - Console Commands
