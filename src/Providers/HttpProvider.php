<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Kunfig\ConfigInterface;
use Phast\Support\DependencyResolver;

/**
 * HTTP middleware service provider.
 */
class HttpProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register HTTP middleware array (can be overridden)
        $container->set('http.middleware', $container->share(function (Container $c) {
            $middleware = [];
            $resolver = $c->get(DependencyResolver::class);
            $config = $c->get(ConfigInterface::class);

            // Load middleware from config file
            $defaultMiddlewarePath = __DIR__.'/../../config/middleware.php';
            $basePath = $config->get('app.base_path');
            $projectMiddlewarePath = ! empty($basePath) ? $basePath.'/config/middleware.php' : null;

            // Try project config first, then fall back to default
            $middlewarePath = null;
            if ($projectMiddlewarePath !== null && file_exists($projectMiddlewarePath)) {
                $middlewarePath = $projectMiddlewarePath;
            } elseif (file_exists($defaultMiddlewarePath)) {
                $middlewarePath = $defaultMiddlewarePath;
            }

            if ($middlewarePath === null) {
                throw new \RuntimeException(
                    'Middleware configuration file not found. '.
                    'Expected either '.($projectMiddlewarePath ?? 'N/A').' or '.$defaultMiddlewarePath
                );
            }

            $middlewareClasses = require $middlewarePath;
            if (! is_array($middlewareClasses)) {
                throw new \RuntimeException(
                    "Middleware configuration file '{$middlewarePath}' must return an array"
                );
            }

            foreach ($middlewareClasses as $middlewareClass) {
                if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                    // Use DependencyResolver for all middleware
                    $middleware[] = $resolver->instantiate($middlewareClass);
                }
            }

            return $middleware;
        }));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
