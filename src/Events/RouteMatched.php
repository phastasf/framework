<?php

declare(strict_types=1);

namespace Phast\Events;

use Psr\Http\Message\ServerRequestInterface;
use Soochak\Event;

/**
 * Event dispatched when a route is successfully matched.
 */
class RouteMatched extends Event
{
    public function __construct(
        ServerRequestInterface $request,
        string $route,
        mixed $target,
        array $parameters = []
    ) {
        parent::__construct(self::class, [
            'request' => $request,
            'route' => $route,
            'target' => $target,
            'parameters' => $parameters,
        ]);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->getParam('request');
    }

    public function getRoute(): string
    {
        return $this->getParam('route');
    }

    public function getTarget(): mixed
    {
        return $this->getParam('target');
    }

    public function getParameters(): array
    {
        return $this->getParam('parameters') ?? [];
    }
}
