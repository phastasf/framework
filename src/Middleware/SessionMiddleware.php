<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Kunfig\ConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to start PHP sessions.
 */
class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();
        }

        // Continue with the request
        return $handler->handle($request);
    }

    /**
     * Configure session cookie parameters from config.
     */
    protected function configureSession(): void
    {
        $cookie = $this->config->get('session.cookie', []);

        // Set cookie name
        if (isset($cookie['name'])) {
            ini_set('session.name', $cookie['name']);
        }

        // Set cookie parameters (PHP 7.3+ array format)
        $options = [
            'lifetime' => $cookie['lifetime'] ?? 7200,
            'path' => $cookie['path'] ?? '/',
            'domain' => $cookie['domain'] ?? null,
            'secure' => $cookie['secure'] ?? false,
            'httponly' => $cookie['httponly'] ?? true,
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ];

        session_set_cookie_params($options);
    }
}
