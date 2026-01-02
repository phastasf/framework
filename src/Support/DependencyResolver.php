<?php

declare(strict_types=1);

namespace Phast\Support;

use Katora\Container;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Reusable dependency resolver for constructor and method arguments.
 */
class DependencyResolver
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Instantiate a class with constructor dependency injection.
     *
     * @param  string  $className  The class name to instantiate
     * @return object The instantiated object
     */
    public function instantiate(string $className): object
    {
        // Check if class is in container
        if ($this->container->has($className)) {
            return $this->container->get($className);
        }

        if (! class_exists($className)) {
            throw new \RuntimeException("Class '{$className}' not found.");
        }

        // Try to resolve constructor arguments
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new $className;
        }

        // Resolve constructor parameters
        $constructorArgs = $this->resolveParameters($constructor->getParameters());

        return new $className(...$constructorArgs);
    }

    /**
     * Resolve method arguments using dependency injection.
     *
     * @param  callable  $callable  The callable to resolve arguments for
     * @param  array  $context  Additional context values (e.g., ['stdio' => $stdio])
     * @return array Resolved arguments
     */
    public function resolveMethodArguments(callable $callable, array $context = []): array
    {
        if (is_array($callable)) {
            // Method call: [object, 'method'] or ['Class', 'method']
            [$classOrObject, $method] = $callable;
            $reflection = new ReflectionMethod($classOrObject, $method);
        } elseif (is_string($callable) && function_exists($callable)) {
            // Named function
            $reflection = new \ReflectionFunction($callable);
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            // Invokable object
            $reflection = new ReflectionMethod($callable, '__invoke');
        } else {
            // Fallback: return empty array
            return [];
        }

        return $this->resolveParameters($reflection->getParameters(), $context);
    }

    /**
     * Resolve reflection parameters using dependency injection.
     *
     * @param  ReflectionParameter[]  $parameters  The parameters to resolve
     * @param  array  $context  Additional context values keyed by parameter name
     * @return array Resolved argument values
     */
    protected function resolveParameters(array $parameters, array $context = []): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // 1. Try context values first (e.g., 'stdio' for command execute methods)
            if (isset($context[$paramName])) {
                $args[] = $context[$paramName];

                continue;
            }

            // 2. Special handling for Container
            if ($paramType && $paramType->getName() === Container::class) {
                $args[] = $this->container;

                continue;
            }

            // 3. Try container by parameter name
            if ($this->container->has($paramName)) {
                $args[] = $this->container->get($paramName);

                continue;
            }

            // 4. Try container by type (class/interface name)
            if ($paramType && ! $paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                if ($this->container->has($typeName)) {
                    $args[] = $this->container->get($typeName);

                    continue;
                }
            }

            // 5. Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();

                continue;
            }

            // 6. If optional (nullable), use null
            if ($param->allowsNull()) {
                $args[] = null;

                continue;
            }

            // Cannot resolve parameter
            $typeHint = $paramType ? $paramType->getName() : 'mixed';
            $declaringClass = $param->getDeclaringClass();
            $className = $declaringClass ? $declaringClass->getName() : 'unknown';
            throw new \RuntimeException(
                "Cannot resolve required parameter '{$paramName}' of type '{$typeHint}' for {$className}. ".
                'Parameter must be available in context, in the container by name or type, '.
                'have a default value, or be nullable.'
            );
        }

        return $args;
    }
}
