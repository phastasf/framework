<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Katora\Container;
use Kunfig\ConfigInterface;
use Phast\Exception\HttpException;
use Phast\Exception\InternalServerErrorException;
use Phew\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Sandesh\ResponseFactory;
use Throwable;

/**
 * Error handler middleware that catches exceptions and converts them to HTTP responses.
 * Should be added first in the middleware pipeline.
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    protected Container $container;

    protected ResponseFactory $responseFactory;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->responseFactory = new ResponseFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (HttpException $e) {
            // Log 5xx errors
            $statusCode = $e->getStatusCode();
            if ($statusCode >= 500 && $statusCode < 600) {
                $this->log($e, $request);
            }

            return $this->handleHttpException($e, $request);
        } catch (Throwable $e) {
            // Log unexpected exceptions
            if ($this->container->has(LoggerInterface::class)) {
                $logger = $this->container->get(LoggerInterface::class);
                $logger->error('Unhandled exception: '.$e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Convert to 500 error
            $httpException = new InternalServerErrorException(
                'Internal Server Error',
                $e
            );

            return $this->handleHttpException($httpException, $request);
        }
    }

    /**
     * Handle HTTP exception and return appropriate response.
     */
    protected function handleHttpException(HttpException $e, ServerRequestInterface $request): ResponseInterface
    {
        $statusCode = $e->getStatusCode();
        $acceptHeader = $request->getHeaderLine('Accept');

        // Check if client accepts JSON
        if (str_contains($acceptHeader, 'application/json')) {
            return $this->createJsonResponse($e, $statusCode);
        }

        // Otherwise render HTML template
        return $this->createHtmlResponse($e, $statusCode);
    }

    /**
     * Create JSON response for HTTP exception.
     */
    protected function createJsonResponse(HttpException $e, int $statusCode): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');

        // Add custom headers from exception
        foreach ($e->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $data = [
            'error' => $this->getErrorName($statusCode),
            'message' => $e->getMessage() ?: $this->getDefaultMessage($statusCode),
        ];

        // Add debug details for 5xx errors if debug is enabled
        if ($statusCode >= 500 && $statusCode < 600 && $this->isDebugEnabled()) {
            $data['debug'] = $this->getDebugDetails($e);
        }

        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);

        return $response;
    }

    /**
     * Create HTML response for HTTP exception.
     */
    protected function createHtmlResponse(HttpException $e, int $statusCode): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');

        // Add custom headers from exception
        foreach ($e->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        // Try to render error template if view is available
        if ($this->container->has(ViewInterface::class)) {
            try {
                $view = $this->container->get(ViewInterface::class);
                $message = $e->getMessage() ?: $this->getDefaultMessage($statusCode);
                $description = $this->getErrorDescription($statusCode);

                $templateVars = [
                    'code' => $statusCode,
                    'message' => $message,
                    'description' => $description,
                ];

                // Add debug details for 5xx errors if debug is enabled
                if ($statusCode >= 500 && $statusCode < 600 && $this->isDebugEnabled()) {
                    $templateVars['debug'] = $this->getDebugDetails($e);
                }

                $content = $view->fetch('error.phtml', $templateVars);
                $response->getBody()->write($content);

                return $response;
            } catch (Throwable $templateError) {
                // If template doesn't exist or fails, fall back to simple message
            }
        }

        // Fallback to simple error message
        $message = $e->getMessage() ?: $this->getDefaultMessage($statusCode);
        $response->getBody()->write($message);

        return $response;
    }

    /**
     * Get error name for status code.
     */
    protected function getErrorName(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Get default message for status code.
     */
    protected function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Get error description for status code.
     */
    protected function getErrorDescription(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'The request was invalid or malformed.',
            401 => 'Authentication is required to access this resource.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for does not exist.',
            405 => 'The request method is not allowed for this resource.',
            422 => 'The request was well-formed but contains semantic errors.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Something went wrong on our end. Please try again later.',
            502 => 'The server received an invalid response from an upstream server.',
            503 => 'The service is temporarily unavailable. Please try again later.',
            default => 'An error occurred while processing your request.',
        };
    }

    /**
     * Log an error for 5xx HTTP exceptions.
     */
    protected function log(HttpException $e, ServerRequestInterface $request): void
    {
        if (! $this->container->has(LoggerInterface::class)) {
            return;
        }

        $logger = $this->container->get(LoggerInterface::class);
        $statusCode = $e->getStatusCode();
        $previous = $e->getPrevious();

        // Build detailed message
        $message = "HTTP {$statusCode} error: {$e->getMessage()}";
        $message .= " in {$e->getFile()}:{$e->getLine()}";
        $message .= " [{$request->getMethod()} {$request->getUri()->getPath()}]";

        $context = [
            'status_code' => $statusCode,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request_method' => $request->getMethod(),
            'request_uri' => (string) $request->getUri(),
        ];

        // Include previous exception details if available (this is the actual error)
        if ($previous !== null) {
            $message .= "\nCaused by: {$previous->getMessage()} in {$previous->getFile()}:{$previous->getLine()}";
            $message .= "\nStack trace:\n{$previous->getTraceAsString()}";

            $context['previous_exception'] = [
                'message' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
                'trace' => $previous->getTraceAsString(),
            ];
        } else {
            $message .= "\nStack trace:\n{$e->getTraceAsString()}";
            $context['trace'] = $e->getTraceAsString();
        }

        $logger->error($message, $context);
    }

    /**
     * Check if debug mode is enabled.
     */
    protected function isDebugEnabled(): bool
    {
        if (! $this->container->has(ConfigInterface::class)) {
            return false;
        }

        $config = $this->container->get(ConfigInterface::class);

        return (bool) $config->get('app.debug', false);
    }

    /**
     * Get debug details for an exception.
     *
     * @return array<string, mixed>
     */
    protected function getDebugDetails(HttpException $e): array
    {
        $previous = $e->getPrevious();
        $exception = $previous ?? $e;

        return [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }
}
