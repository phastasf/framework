<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Jweety\EncoderInterface;
use Jweety\Exception\InvalidSignatureException;
use Jweety\Exception\InvalidTokenException;
use Jweety\Exception\TokenExpiredException;
use Kunfig\ConfigInterface;
use Phast\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JWT Authentication middleware.
 * Validates JWT tokens and adds claims to request attributes.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public const AUTH_ATTRIBUTE = '_auth_claims';

    protected EncoderInterface $encoder;

    protected ConfigInterface $config;

    /**
     * Paths/prefixes to include (empty = all paths).
     *
     * @var array<string>
     */
    protected array $include = [];

    /**
     * Paths/prefixes to exclude.
     *
     * @var array<string>
     */
    protected array $exclude = [];

    /**
     * HTTP header name to extract token from (default: Authorization).
     */
    protected string $headerName = 'Authorization';

    /**
     * Token prefix in header (default: Bearer).
     */
    protected string $tokenPrefix = 'Bearer';

    public function __construct(
        EncoderInterface $encoder,
        ConfigInterface $config
    ) {
        $this->encoder = $encoder;
        $this->config = $config;

        // Load configuration values
        $include = $this->config->get('auth.middleware.include', []);
        $exclude = $this->config->get('auth.middleware.exclude', []);
        $this->headerName = $this->config->get('auth.middleware.header', 'Authorization');
        $this->tokenPrefix = $this->config->get('auth.middleware.prefix', 'Bearer');

        // Convert ConfigInterface to array if needed
        if ($include instanceof ConfigInterface) {
            $include = $include->all();
        }
        if ($exclude instanceof ConfigInterface) {
            $exclude = $exclude->all();
        }

        // Ensure arrays
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

        // Check if path should be included (if include list is not empty)
        if (! empty($this->include) && ! $this->shouldInclude($path)) {
            return $handler->handle($request);
        }

        // Extract and validate token
        $token = $this->extractToken($request);

        if ($token === null) {
            throw new UnauthorizedException(
                'Missing authentication token',
                null,
                ['WWW-Authenticate' => $this->tokenPrefix.' realm="API"']
            );
        }

        try {
            // Parse and validate token
            $claims = $this->encoder->parse($token);

            // Add claims to request attributes
            $request = $request->withAttribute(self::AUTH_ATTRIBUTE, $claims);

            return $handler->handle($request);
        } catch (TokenExpiredException $e) {
            throw new UnauthorizedException(
                'Token has expired',
                $e,
                ['WWW-Authenticate' => $this->tokenPrefix.' realm="API"']
            );
        } catch (InvalidSignatureException $e) {
            throw new UnauthorizedException(
                'Invalid token signature',
                $e,
                ['WWW-Authenticate' => $this->tokenPrefix.' realm="API"']
            );
        } catch (InvalidTokenException $e) {
            throw new UnauthorizedException(
                'Invalid token',
                $e,
                ['WWW-Authenticate' => $this->tokenPrefix.' realm="API"']
            );
        }
    }

    /**
     * Extract JWT token from request headers.
     */
    protected function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine($this->headerName);

        if (empty($header)) {
            return null;
        }

        // Check if header starts with token prefix
        $prefix = $this->tokenPrefix.' ';
        if (! str_starts_with($header, $prefix)) {
            return null;
        }

        // Extract token after prefix
        $token = substr($header, strlen($prefix));

        return trim($token) ?: null;
    }

    /**
     * Check if path should be excluded.
     */
    protected function shouldExclude(string $path): bool
    {
        foreach ($this->exclude as $excludePath) {
            if ($this->pathMatches($path, $excludePath)) {
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
        foreach ($this->include as $includePath) {
            if ($this->pathMatches($path, $includePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if path matches a pattern (exact match or prefix).
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Prefix match (if pattern ends with *)
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            if ($prefix === '' || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        // Prefix match (if pattern doesn't end with /, check if path starts with pattern/)
        if (! str_ends_with($pattern, '/') && str_starts_with($path, $pattern.'/')) {
            return true;
        }

        return false;
    }
}
