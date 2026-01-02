<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Tez\Router;

/**
 * Router service provider.
 */
class RouterProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register router
        $container->set('router', $container->share(function (Container $c) {
            $config = $c->get('config');

            // Try to load from cache if it exists (regardless of debug mode)
            $precompiled = $this->loadPrecompiledRoutes($config);

            $router = new Router($precompiled);

            // Load routes (only if not using precompiled routes)
            if ($precompiled === null) {
                $this->loadRoutes($router, $c);

                // If not in debug mode and cache doesn't exist, create cache
                $debug = $config->get('app.debug', true);
                if (! $debug) {
                    $this->cacheRoutes($router, $config);
                }
            }

            return $router;
        }));

        $container->set(Router::class, fn (Container $c) => $c->get('router'));
    }

    /**
     * Load precompiled routes from cache file.
     *
     * @return array<int, array{0: string, 1: array<string>|null, 2: mixed, 3?: string, 4?: array<string>}>|null
     */
    protected function loadPrecompiledRoutes($config): ?array
    {
        $basePath = $config->get('app.base_path');
        if (empty($basePath)) {
            return null;
        }

        $cacheFile = $basePath.'/storage/cache/routes.php';

        if (! file_exists($cacheFile)) {
            return null;
        }

        try {
            $precompiled = require $cacheFile;
            if (is_array($precompiled)) {
                return $precompiled;
            }
        } catch (\Throwable $e) {
            // Invalid cache file, will regenerate
        }

        return null;
    }

    /**
     * Cache compiled routes to disk.
     */
    protected function cacheRoutes(Router $router, $config): void
    {
        $basePath = $config->get('app.base_path');
        $cacheDir = $basePath.'/storage/cache';
        $cacheFile = $cacheDir.'/routes.php';

        // Ensure cache directory exists
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Dump compiled routes to cache file
        try {
            $router->dump($cacheFile);
        } catch (\Throwable $e) {
            // Silently fail if caching fails
        }
    }

    /**
     * Load application routes.
     */
    protected function loadRoutes(Router $router, Container $container): void
    {
        // Get routes file path from config
        $config = $container->get('config');
        $routesFile = $config->get('app.routes.web');

        // If not configured, fall back to default
        if (empty($routesFile)) {
            $basePath = $config->get('app.base_path');
            $routesFile = $basePath.'/routes/web.php';
        }

        // Handle relative paths
        if (! str_starts_with($routesFile, '/')) {
            $basePath = $config->get('app.base_path');
            $routesFile = $basePath.'/'.ltrim($routesFile, '/');
        }

        if (file_exists($routesFile)) {
            $registerRoutes = require $routesFile;
            if (is_callable($registerRoutes)) {
                $registerRoutes($router);
            }
        }
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
