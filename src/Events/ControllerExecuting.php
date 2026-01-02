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
        ServerRequestInterface $request,
        string $controller,
        string $action,
        array $parameters = []
    ) {
        parent::__construct(self::class, [
            'request' => $request,
            'controller' => $controller,
            'action' => $action,
            'parameters' => $parameters,
        ]);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->getParam('request');
    }

    public function getController(): string
    {
        return $this->getParam('controller');
    }

    public function getAction(): string
    {
        return $this->getParam('action');
    }

    public function getParameters(): array
    {
        return $this->getParam('parameters') ?? [];
    }
}
