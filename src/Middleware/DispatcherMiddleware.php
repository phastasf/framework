<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Katora\Container;
use Kunfig\ConfigInterface;
use Phast\Controller;
use Phast\Exception\HttpException;
use Phast\Exception\InternalServerErrorException;
use Phast\Exception\MethodNotAllowedException;
use Phast\Exception\NotFoundException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;
use Sandesh\ResponseFactory;
use Tez\MatchResult;
use Throwable;

/**
 * Dispatcher middleware that handles route dispatching.
 * Gets the match result from request attributes and dispatches the route action.
 */
class DispatcherMiddleware implements MiddlewareInterface
{
    protected Container $container;

    protected ResponseFactory $responseFactory;

    protected string $controllerNamespace = '';

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->responseFactory = new ResponseFactory;

        // Get controller namespace from config
        if ($container->has(ConfigInterface::class)) {
            $config = $container->get(ConfigInterface::class);
            $this->controllerNamespace = $config->get('app.controllers.namespace', 'App\\Controllers');
        }
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Get route match result from request attributes
        $matchResult = $request->getAttribute(RoutingMiddleware::ROUTE_MATCH_ATTRIBUTE);

        if ($matchResult === null) {
            // No match result found, throw 404
            throw new NotFoundException;
        }

        // Ensure match result is an array with at least status
        if (! is_array($matchResult) || ! isset($matchResult[0])) {
            throw new NotFoundException;
        }

        $status = $matchResult[0];
        $target = $matchResult[1] ?? null;
        $routeParams = $matchResult[2] ?? [];

        // Handle different match statuses
        if ($status === MatchResult::NOT_ALLOWED) {
            throw new MethodNotAllowedException;
        }

        if ($status !== MatchResult::FOUND) {
            // No route matched, throw 404
            throw new NotFoundException;
        }

        // Dispatch the route target
        try {
            return $this->dispatch($request, $target, $routeParams ?? []);
        } catch (HttpException $e) {
            // Re-throw HTTP exceptions as-is
            throw $e;
        } catch (Throwable $e) {
            // Convert to 500 error - ErrorHandlerMiddleware will log it
            throw new InternalServerErrorException('Internal Server Error', $e);
        }
    }

    /**
     * Dispatch a route target.
     */
    protected function dispatch(ServerRequestInterface $request, mixed $target, array $routeParams): ResponseInterface
    {
        // If target is a callable
        if (is_callable($target)) {
            $args = $this->resolveActionArguments($target, $request, $routeParams);
            $result = $target(...$args);

            if (! $result instanceof ResponseInterface) {
                $response = $this->responseFactory->createResponse(200);
                $response->getBody()->write((string) $result);

                return $response;
            }

            return $result;
        }

        // If target is a string like "Controller@method"
        if (is_string($target) && str_contains($target, '@')) {
            [$controllerClass, $method] = explode('@', $target, 2);

            // If class doesn't start with a backslash, it's not a fully qualified name
            // Prefix it with the configured controller namespace
            if (! str_starts_with($controllerClass, '\\') && ! str_contains($controllerClass, '\\')) {
                $controllerClass = $this->controllerNamespace.'\\'.$controllerClass;
            }

            // Instantiate controller with constructor DI
            $controller = $this->instantiateController($controllerClass);

            if (! method_exists($controller, $method)) {
                throw new InternalServerErrorException("Method {$method} not found in {$controllerClass}");
            }

            // Resolve method arguments and call
            $args = $this->resolveActionArguments([$controller, $method], $request, $routeParams);
            $result = $controller->$method(...$args);

            if (! $result instanceof ResponseInterface) {
                $response = $this->responseFactory->createResponse(200);
                $response->getBody()->write((string) $result);

                return $response;
            }

            return $result;
        }

        // Default response
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write((string) $target);

        return $response;
    }

    /**
     * Instantiate a controller with constructor dependency injection.
     * Constructor arguments are resolved by name or type (NOT route params).
     */
    protected function instantiateController(string $controllerClass): object
    {
        // Check if controller is in container
        if ($this->container->has($controllerClass)) {
            return $this->container->get($controllerClass);
        }

        if (! class_exists($controllerClass)) {
            throw new InternalServerErrorException("Controller {$controllerClass} not found");
        }

        // Check if controller extends base Controller and needs container injection
        $isBaseController = is_subclass_of($controllerClass, Controller::class);
        if ($isBaseController) {
            return new $controllerClass($this->container);
        }

        // Try to resolve constructor arguments
        $reflection = new ReflectionClass($controllerClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new $controllerClass;
        }

        // Resolve constructor parameters (by name or type only, NOT route params)
        $constructorArgs = $this->resolveConstructorArguments($constructor->getParameters());

        return new $controllerClass(...$constructorArgs);
    }

    /**
     * Resolve constructor arguments (by name or type only, NOT route params).
     *
     * @param  ReflectionParameter[]  $parameters  The constructor parameters
     * @return array Resolved argument values
     */
    protected function resolveConstructorArguments(array $parameters): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // Special handling for Container
            if ($paramType && $paramType->getName() === Container::class) {
                $args[] = $this->container;

                continue;
            }

            // 1. Try container by parameter name
            if ($this->container->has($paramName)) {
                $args[] = $this->container->get($paramName);

                continue;
            }

            // 2. Try container by type (class/interface name)
            if ($paramType && ! $paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                if ($this->container->has($typeName)) {
                    $args[] = $this->container->get($typeName);

                    continue;
                }
            }

            // 3. Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();

                continue;
            }

            // 4. If optional (nullable), use null
            if ($param->allowsNull()) {
                $args[] = null;

                continue;
            }

            // Cannot resolve parameter
            throw new \RuntimeException("Cannot resolve constructor parameter '{$paramName}' for {$param->getDeclaringClass()->getName()}");
        }

        return $args;
    }

    /**
     * Resolve arguments for a route action using dependency injection.
     * Includes route params, container by name, and container by type.
     *
     * @param  callable  $callable  The callable to resolve arguments for
     * @param  ServerRequestInterface  $request  The current request
     * @param  array  $routeParams  Route parameters from the match
     * @return array Resolved arguments
     */
    protected function resolveActionArguments(callable $callable, ServerRequestInterface $request, array $routeParams): array
    {
        if (is_array($callable)) {
            // Method call: [object, 'method'] or ['Class', 'method']
            [$classOrObject, $method] = $callable;
            $reflection = new ReflectionMethod($classOrObject, $method);
        } elseif (is_string($callable) && function_exists($callable)) {
            // Named function
            $reflection = new ReflectionFunction($callable);
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            // Invokable object
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else {
            // Fallback: just pass request
            return [$request];
        }

        return $this->resolveActionReflectionParameters($reflection->getParameters(), $request, $routeParams);
    }

    /**
     * Resolve reflection parameters for action arguments.
     * Includes route params, container by name, and container by type.
     *
     * @param  ReflectionParameter[]  $parameters  The parameters to resolve
     * @param  ServerRequestInterface  $request  The current request
     * @param  array  $routeParams  Route parameters from the match
     * @return array Resolved argument values
     */
    protected function resolveActionReflectionParameters(array $parameters, ServerRequestInterface $request, array $routeParams): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // Special handling for request objects
            if ($paramName === 'request' || ($paramType && $this->isRequestType($paramType))) {
                $args[] = $request;

                continue;
            }

            // 1. Try route parameters first
            if (isset($routeParams[$paramName])) {
                $args[] = $routeParams[$paramName];

                continue;
            }

            // 2. Try container by parameter name
            if ($this->container->has($paramName)) {
                $args[] = $this->container->get($paramName);

                continue;
            }

            // 3. Try container by type (class/interface name)
            if ($paramType && ! $paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                if ($this->container->has($typeName)) {
                    $args[] = $this->container->get($typeName);

                    continue;
                }
            }

            // 4. Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();

                continue;
            }

            // 5. If optional (nullable), use null
            if ($param->allowsNull()) {
                $args[] = null;

                continue;
            }

            // Cannot resolve parameter - throw exception
            $typeHint = $paramType ? $paramType->getName() : 'mixed';
            throw new RuntimeException(
                "Cannot resolve required parameter '{$paramName}' of type '{$typeHint}' for route action. ".
                'Parameter must be available as a route parameter, in the container by name or type, '.
                'have a default value, or be nullable.'
            );
        }

        return $args;
    }

    /**
     * Check if a type is a request type.
     *
     * @param  \ReflectionNamedType|\ReflectionUnionType|\ReflectionIntersectionType  $type  The type to check
     * @return bool True if the type is a request type
     */
    protected function isRequestType($type): bool
    {
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            return $typeName === ServerRequestInterface::class
                || $typeName === RequestInterface::class
                || $typeName === 'Psr\Http\Message\ServerRequestInterface'
                || $typeName === 'Psr\Http\Message\RequestInterface';
        }

        // For union/intersection types, check if any of them is a request type
        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            foreach ($type->getTypes() as $subType) {
                if ($this->isRequestType($subType)) {
                    return true;
                }
            }
        }

        return false;
    }
}
