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
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $controller,
        string $action
    ) {
        parent::__construct(self::class, [
            'request' => $request,
            'response' => $response,
            'controller' => $controller,
            'action' => $action,
        ]);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->getParam('request');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->getParam('response');
    }

    public function getController(): string
    {
        return $this->getParam('controller');
    }

    public function getAction(): string
    {
        return $this->getParam('action');
    }
}
