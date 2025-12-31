<?php

declare(strict_types=1);

namespace Phast;

use Katora\Container;
use Phast\Entrypoint\ConsoleEntrypoint;
use Phast\Entrypoint\WebEntrypoint;
use Phast\Providers\AuthProvider;
use Phast\Providers\CacheProvider;
use Phast\Providers\CaptchaProvider;
use Phast\Providers\ClockProvider;
use Phast\Providers\ConfigProvider;
use Phast\Providers\DatabaseProvider;
use Phast\Providers\EventProvider;
use Phast\Providers\HttpMessageProvider;
use Phast\Providers\HttpProvider;
use Phast\Providers\LoggingProvider;
use Phast\Providers\MailProvider;
use Phast\Providers\MigrationProvider;
use Phast\Providers\QueueProvider;
use Phast\Providers\RouterProvider;
use Phast\Providers\SessionProvider;
use Phast\Providers\ViewProvider;

/**
 * Framework bootstrap class.
 */
class Framework
{
    protected Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? new Container;
        $this->bootstrap();
    }

    /**
     * Bootstrap the framework by registering all providers.
     */
    protected function bootstrap(): void
    {
        // Register ConfigProvider first (other providers depend on it)
        $this->container->install(new ConfigProvider);

        // Register all other providers
        $this->container->install(new CacheProvider);
        $this->container->install(new DatabaseProvider);
        $this->container->install(new MigrationProvider);
        $this->container->install(new LoggingProvider);
        $this->container->install(new RouterProvider);
        $this->container->install(new AuthProvider);
        $this->container->install(new ViewProvider);
        $this->container->install(new SessionProvider);
        $this->container->install(new CaptchaProvider);
        $this->container->install(new QueueProvider);
        $this->container->install(new MailProvider);
        $this->container->install(new ClockProvider);
        $this->container->install(new HttpMessageProvider);
        $this->container->install(new EventProvider);
        $this->container->install(new HttpProvider);

        // Register entrypoints
        $this->container->set('entrypoint.console', $this->container->share(function (Container $c) {
            return new ConsoleEntrypoint($c);
        }));

        $this->container->set('entrypoint.web', $this->container->share(function (Container $c) {
            $entrypoint = new WebEntrypoint($c);
            $entrypoint->buildPipeline();

            return $entrypoint;
        }));

        // Register entrypoint classes
        $this->container->set(ConsoleEntrypoint::class, fn (Container $c) => $c->get('entrypoint.console'));
        $this->container->set(WebEntrypoint::class, fn (Container $c) => $c->get('entrypoint.web'));
    }

    /**
     * Get the container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the console entrypoint.
     */
    public function getConsoleEntrypoint(): ConsoleEntrypoint
    {
        return $this->container->get(ConsoleEntrypoint::class);
    }

    /**
     * Get the web entrypoint.
     */
    public function getWebEntrypoint(): WebEntrypoint
    {
        return $this->container->get(WebEntrypoint::class);
    }
}
