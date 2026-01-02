<?php

declare(strict_types=1);

namespace Phast\Events;

use Psr\Http\Message\ServerRequestInterface;
use Soochak\Event;

/**
 * Event dispatched before a controller action is executed.
 */
class ControllerExecuting extends Event
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly string $controller,
        public readonly string $action,
        public readonly array $parameters = []
    ) {}
}
