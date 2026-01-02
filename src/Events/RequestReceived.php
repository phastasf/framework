<?php

declare(strict_types=1);

namespace Phast\Events;

use Psr\Http\Message\ServerRequestInterface;
use Soochak\Event;

/**
 * Event dispatched when an HTTP request is received.
 */
class RequestReceived extends Event
{
    public function __construct(
        public readonly ServerRequestInterface $request
    ) {}
}
