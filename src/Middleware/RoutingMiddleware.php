<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Katora\Container;
use Phast\Events\RouteMatched;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tez\MatchResult;
use Tez\Router;

/**
 * Routing middleware that matches requests to routes.
 * Adds the match result to request attributes for the dispatcher.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    public const ROUTE_MATCH_ATTRIBUTE = '_route_match';

    protected Router $router;

    protected ?EventDispatcherInterface $dispatcher = null;

    public function __construct(Router $router, ?Container $container = null)
    {
        $this->router = $router;

        // Get event dispatcher from container if available
        if ($container !== null && $container->has(EventDispatcherInterface::class)) {
            $this->dispatcher = $container->get(EventDispatcherInterface::class);
        }
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $result = $this->router->match($path, $method);

        // Add match result to request attributes
        $request = $request->withAttribute(self::ROUTE_MATCH_ATTRIBUTE, $result);

        // Add route params to request attributes if route was found
        if ($result[0] === MatchResult::FOUND) {
            $params = $result[2] ?? [];
            foreach ($params as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }

            // Dispatch route matched event
            if ($this->dispatcher !== null) {
                $target = $result[1] ?? null;
                $this->dispatcher->dispatch(new RouteMatched($request, $path, $target, $params));
            }
        }

        return $handler->handle($request);
    }
}
