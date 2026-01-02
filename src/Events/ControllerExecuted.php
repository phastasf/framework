<?php

declare(strict_types=1);

namespace Phast\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soochak\Event;

/**
 * Event dispatched after a controller action is executed.
 */
class ControllerExecuted extends Event
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly ResponseInterface $response,
        public readonly string $controller,
        public readonly string $action
    ) {}
}
