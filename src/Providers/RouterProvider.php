<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;
use Tez\Router;

/**
 * Router service provider.
 */
class RouterProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register router
        $container->set('router', $container->share(function (Container $c) {
            $router = new Router;

            // Load routes
            $this->loadRoutes($router, $c);

            return $router;
        }));

        $container->set(Router::class, fn (Container $c) => $c->get('router'));
    }

    /**
     * Load application routes.
     */
    protected function loadRoutes(Router $router, Container $container): void
    {
        // Get routes file path
        $config = $container->get('config');
        $basePath = $config->get('app.base_path');
        $routesFile = $basePath.'/routes/web.php';

        if (file_exists($routesFile)) {
            $registerRoutes = require $routesFile;
            if (is_callable($registerRoutes)) {
                $registerRoutes($router);
            }
        }
    }
}
