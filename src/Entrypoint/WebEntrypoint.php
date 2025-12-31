<?php

declare(strict_types=1);

namespace Phast\Entrypoint;

use Katora\Container;
use Sandesh\ServerRequestFactory;
use Sandesh\ServerResponseSender;
use Tez\Router;
use Vidyut\Pipeline;

/**
 * Web entrypoint for Phast framework.
 */
class WebEntrypoint
{
    protected Container $container;

    protected Pipeline $pipeline;

    protected Router $router;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? new Container;
        $this->pipeline = new Pipeline;

        // Get router from container if available, otherwise create new one
        if ($this->container->has(Router::class)) {
            $this->router = $this->container->get(Router::class);
        } elseif ($this->container->has('router')) {
            $this->router = $this->container->get('router');
        } else {
            $this->router = new Router;
        }
    }

    /**
     * Build the middleware pipeline.
     */
    public function buildPipeline(): self
    {
        // Get middleware from container service (can be overridden)
        $middleware = $this->container->has('http.middleware')
            ? $this->container->get('http.middleware')
            : [];

        // Add each middleware to the pipeline
        foreach ($middleware as $mw) {
            $this->pipeline->pipe($mw);
        }

        return $this;
    }

    /**
     * Get the router instance.
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the pipeline instance.
     */
    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

    /**
     * Handle the incoming request.
     */
    public function handle(): void
    {
        $requestFactory = new ServerRequestFactory;
        $request = $requestFactory->createServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER
        );

        // ErrorHandlerMiddleware will catch all exceptions
        $response = $this->pipeline->handle($request);

        $sender = new ServerResponseSender;
        $sender->send($response);
    }
}
