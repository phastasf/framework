<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Kunfig\ConfigInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS (Cross-Origin Resource Sharing) middleware.
 * Handles CORS headers for cross-origin requests.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Allowed origins ('*' or array of origins).
     *
     * @var string|array<string>
     */
    private readonly string|array $allowedOrigins;

    /**
     * Allowed HTTP methods.
     *
     * @var array<string>
     */
    private readonly array $allowedMethods;

    /**
     * Allowed request headers ('*' or array of headers).
     *
     * @var string|array<string>
     */
    private readonly string|array $allowedHeaders;

    /**
     * Headers that can be exposed to the client.
     *
     * @var array<string>
     */
    private readonly array $exposedHeaders;

    private readonly int $maxAge;

    private readonly bool $allowCredentials;

    /**
     * Paths/prefixes to include (empty = all paths).
     *
     * @var array<string>
     */
    private readonly array $include;

    /**
     * Paths/prefixes to exclude.
     *
     * @var array<string>
     */
    private readonly array $exclude;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ResponseFactoryInterface $responseFactory
    ) {
        // Get configuration values
        $allowedOrigins = $this->config->get('cors.allowed_origins', '*');
        $allowedMethods = $this->config->get('cors.allowed_methods', []);
        $allowedHeaders = $this->config->get('cors.allowed_headers', '*');
        $exposedHeaders = $this->config->get('cors.exposed_headers', []);
        $maxAge = $this->config->get('cors.max_age', 86400);
        $allowCredentials = $this->config->get('cors.allow_credentials', false);
        $include = $this->config->get('cors.include', []);
        $exclude = $this->config->get('cors.exclude', []);

        // Convert ConfigInterface to array if needed
        if ($allowedMethods instanceof ConfigInterface) {
            $allowedMethods = $allowedMethods->all();
        }
        if ($exposedHeaders instanceof ConfigInterface) {
            $exposedHeaders = $exposedHeaders->all();
        }
        if ($include instanceof ConfigInterface) {
            $include = $include->all();
        }
        if ($exclude instanceof ConfigInterface) {
            $exclude = $exclude->all();
        }

        // Ensure arrays
        $this->allowedOrigins = is_string($allowedOrigins) ? $allowedOrigins : (is_array($allowedOrigins) ? $allowedOrigins : '*');
        $this->allowedMethods = is_array($allowedMethods) ? $allowedMethods : [];
        $this->allowedHeaders = is_string($allowedHeaders) ? $allowedHeaders : (is_array($allowedHeaders) ? $allowedHeaders : '*');
        $this->exposedHeaders = is_array($exposedHeaders) ? $exposedHeaders : [];
        $this->maxAge = is_int($maxAge) ? $maxAge : 86400;
        $this->allowCredentials = is_bool($allowCredentials) ? $allowCredentials : false;
        $this->include = is_array($include) ? $include : [];
        $this->exclude = is_array($exclude) ? $exclude : [];
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $path = $request->getUri()->getPath();

        // Check if path should be excluded
        if ($this->shouldExclude($path)) {
            return $handler->handle($request);
        }

        // Check if path should be included
        if (! $this->shouldInclude($path)) {
            return $handler->handle($request);
        }

        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        // Handle actual request - add CORS headers to response
        $response = $handler->handle($request);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight OPTIONS request.
     */
    protected function handlePreflight(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(204);

        // Get origin from request
        $origin = $request->getHeaderLine('Origin');

        // Validate origin
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $this->getOriginHeader($origin, $request));
        }

        // Add allowed methods
        if (! empty($this->allowedMethods)) {
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        }

        // Add allowed headers
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if (! empty($requestHeaders)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $this->getHeadersHeader($requestHeaders));
        } elseif ($this->allowedHeaders !== '*') {
            $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', (array) $this->allowedHeaders));
        }

        // Add max age
        if ($this->maxAge > 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
        }

        // Add credentials
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Add CORS headers to response.
     */
    protected function addCorsHeaders(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');

        // Validate origin
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $this->getOriginHeader($origin, $request));
        }

        // Add exposed headers
        if (! empty($this->exposedHeaders)) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }

        // Add credentials
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Check if origin is allowed.
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        if ($this->allowedOrigins === '*') {
            return true;
        }

        if (is_array($this->allowedOrigins)) {
            return in_array($origin, $this->allowedOrigins, true);
        }

        return false;
    }

    /**
     * Get origin header value.
     * When allow_credentials is true, cannot use '*' - must return specific origin.
     */
    protected function getOriginHeader(string $origin, ServerRequestInterface $request): string
    {
        // If credentials are allowed, we cannot use '*' - must return specific origin
        if ($this->allowCredentials && $this->allowedOrigins === '*') {
            return $origin; // Return the requesting origin
        }

        if ($this->allowedOrigins === '*') {
            return '*';
        }

        if (is_array($this->allowedOrigins) && in_array($origin, $this->allowedOrigins, true)) {
            return $origin;
        }

        return '';
    }

    /**
     * Get headers header value.
     */
    protected function getHeadersHeader(string $requestHeaders): string
    {
        if ($this->allowedHeaders === '*') {
            return $requestHeaders;
        }

        if (is_array($this->allowedHeaders)) {
            $requested = array_map('trim', explode(',', $requestHeaders));
            $allowed = array_intersect($requested, $this->allowedHeaders);

            return implode(', ', $allowed);
        }

        return '';
    }

    /**
     * Check if path should be excluded.
     */
    protected function shouldExclude(string $path): bool
    {
        if (empty($this->exclude)) {
            return false;
        }

        foreach ($this->exclude as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if path should be included.
     */
    protected function shouldInclude(string $path): bool
    {
        if (empty($this->include)) {
            return true; // Include all if no specific includes
        }

        foreach ($this->include as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
