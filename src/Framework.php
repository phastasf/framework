<?php

declare(strict_types=1);

namespace Phast;

use Katora\Container;
use Phast\Entrypoint\ConsoleEntrypoint;
use Phast\Entrypoint\WebEntrypoint;
use Phast\Support\DependencyResolver;

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
        // Register DependencyResolver as a shared service (used by multiple components)
        $this->container->set(DependencyResolver::class, $this->container->share(function (Container $c) {
            return new DependencyResolver($c);
        }));

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
        // Load providers from config file
        $defaultProvidersPath = __DIR__.'/../config/providers.php';
        $projectProvidersPath = defined('BASE_PATH') ? BASE_PATH.'/config/providers.php' : null;

        // Try project config first, then fall back to default
        $providersPath = null;
        if ($projectProvidersPath !== null && file_exists($projectProvidersPath)) {
            $providersPath = $projectProvidersPath;
        } elseif (file_exists($defaultProvidersPath)) {
            $providersPath = $defaultProvidersPath;
        }

        if ($providersPath === null) {
            throw new \RuntimeException(
                'Providers configuration file not found. '.
                'Expected either '.($projectProvidersPath ?? 'N/A').' or '.$defaultProvidersPath
            );
        }

        $providerClasses = require $providersPath;
        if (! is_array($providerClasses)) {
            throw new \RuntimeException(
                "Providers configuration file '{$providersPath}' must return an array"
            );
        }

        $providers = [];
        foreach ($providerClasses as $providerClass) {
            if (is_string($providerClass) && class_exists($providerClass)) {
                $providers[] = new $providerClass;
            }
        }

        return $providers;
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
