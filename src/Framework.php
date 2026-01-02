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
        // Get all providers
        $providers = $this->getProviders();

        // Register all providers
        foreach ($providers as $provider) {
            $this->container->install($provider);
        }

        // Initialize all providers after registration
        foreach ($providers as $provider) {
            if ($provider instanceof Providers\ProviderInterface) {
                $provider->init($this->container);
            }
        }

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
     * Get the service providers.
     *
     * @return array<int, \Phast\Providers\ProviderInterface>
     */
    protected function getProviders(): array
    {
        return [
            // ConfigProvider must be first (other providers depend on it)
            new ConfigProvider,
            new CacheProvider,
            new DatabaseProvider,
            new MigrationProvider,
            new LoggingProvider,
            new RouterProvider,
            new AuthProvider,
            new ViewProvider,
            new SessionProvider,
            new CaptchaProvider,
            new QueueProvider,
            new MailProvider,
            new ClockProvider,
            new HttpMessageProvider,
            new EventProvider,
            new HttpProvider,
        ];
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
