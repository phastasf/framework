<?php

declare(strict_types=1);

namespace Phast\Providers;

use Jweety\EncoderInterface;
use Katora\Container;
use Kunfig\ConfigInterface;
use Phast\Middleware\AuthMiddleware;
use Phast\Middleware\DispatcherMiddleware;
use Phast\Middleware\ErrorHandlerMiddleware;
use Phast\Middleware\RoutingMiddleware;
use Phast\Middleware\SessionMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Tez\Router;

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

            // Error handler must be first to catch all exceptions
            $middleware[] = new ErrorHandlerMiddleware($c);

            // Start sessions (needed for flash messages, captcha, etc.)
            $middleware[] = new SessionMiddleware;

            // Add authentication middleware if configured
            $authMiddleware = $this->createAuthMiddleware($c);
            if ($authMiddleware !== null) {
                $middleware[] = $authMiddleware;
            }

            // Add routing middleware (matches routes)
            $router = $c->has(Router::class) ? $c->get(Router::class) : $c->get('router');
            $middleware[] = new RoutingMiddleware($router, $c);

            // Add dispatcher middleware (dispatches matched routes)
            $middleware[] = new DispatcherMiddleware($c);

            return $middleware;
        }));
    }

    /**
     * Create authentication middleware if configured.
     */
    protected function createAuthMiddleware(Container $container): ?MiddlewareInterface
    {
        if (! $container->has(EncoderInterface::class)) {
            return null;
        }

        $config = $container->get(ConfigInterface::class);

        // Get configuration values using dot notation
        $include = $config->get('auth.middleware.include', []);
        $exclude = $config->get('auth.middleware.exclude', []);
        $required = (bool) $config->get('auth.middleware.required', true);
        $headerName = $config->get('auth.middleware.header', 'Authorization');
        $tokenPrefix = $config->get('auth.middleware.prefix', 'Bearer');

        // Convert ConfigInterface to array if needed
        if ($include instanceof ConfigInterface) {
            $include = $include->all();
        }
        if ($exclude instanceof ConfigInterface) {
            $exclude = $exclude->all();
        }

        // Ensure arrays
        if (! is_array($include)) {
            $include = [];
        }
        if (! is_array($exclude)) {
            $exclude = [];
        }

        // Create and return auth middleware
        $encoder = $container->get(EncoderInterface::class);

        return new AuthMiddleware(
            $encoder,
            $include,
            $exclude,
            $required,
            $headerName,
            $tokenPrefix
        );
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
