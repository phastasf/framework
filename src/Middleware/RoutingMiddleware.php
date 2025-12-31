<?php

declare(strict_types=1);

namespace Phast\Middleware;

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

    public function __construct(Router $router)
    {
        $this->router = $router;
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
        }

        return $handler->handle($request);
    }
}
