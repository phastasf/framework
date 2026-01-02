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

## Routing

```php
// routes/web.php
return function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->get('/users/{id}', 'UserController@show');
    $router->post('/users', 'UserController@store');
};
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
- `g:job` - Generate job
- `g:migration` - Generate migration
- `g:model` - Generate model

### Database

- `m:up` - Run pending migrations
- `m:down [count]` - Rollback migrations (default: 1)

### Development

- `serve` - Start development server
- `uncache` - Clear cached config, routes, and application cache
- `worker` - Run queue worker

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
- [Tez](https://github.com/vaibhavpandeyvpz/tez) - Routing
- [Vidyut](https://github.com/vaibhavpandeyvpz/vidyut) - Middleware Pipeline
- [Datum](https://github.com/vaibhavpandeyvpz/datum) - ORM
- [Kram](https://github.com/vaibhavpandeyvpz/kram) - Migrations
- [Phew](https://github.com/vaibhavpandeyvpz/phew) - Templates
- [Filtr](https://github.com/vaibhavpandeyvpz/filtr) - Validation
- [Drishti](https://github.com/vaibhavpandeyvpz/drishti) - Logging
- [Godam](https://github.com/vaibhavpandeyvpz/godam) - Caching
- [Qatar](https://github.com/vaibhavpandeyvpz/qatar) - Queues
- [Jweety](https://github.com/vaibhavpandeyvpz/jweety) - JWT
- And more...
