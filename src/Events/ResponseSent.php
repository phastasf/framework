<?php

declare(strict_types=1);

namespace Phast\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soochak\Event;

/**
 * Event dispatched after a response is sent to the client.
 */
class ResponseSent extends Event
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly ResponseInterface $response
    ) {}
}
