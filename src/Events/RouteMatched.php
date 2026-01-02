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
        public readonly ServerRequestInterface $request,
        public readonly string $route,
        public readonly mixed $target,
        public readonly array $parameters = []
    ) {}
}
