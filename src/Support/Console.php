<?php

declare(strict_types=1);

namespace Phast\Support;

use Clip\Command;
use Clip\Console as BaseConsole;
use Katora\Container;

/**
 * Console implementation with container-based dependency injection.
 */
class Console extends BaseConsole
{
    protected DependencyResolver $resolver;

    public function __construct(
        array $commands = [],
        private readonly ?Container $container = null
    ) {
        parent::__construct($commands);
        $this->resolver = new DependencyResolver($this->container ?? new Container);
    }

    /**
     * Resolves a command from a class name or returns the instance.
     * Uses dependency injection to resolve constructor arguments.
     *
     * @param  string|Command  $command  Command class name or instance
     * @return Command The command instance
     */
    protected function resolveCommand(string|Command $command): Command
    {
        if ($command instanceof Command) {
            $instance = $command;
        } else {
            if (! class_exists($command)) {
                throw new \RuntimeException("Command class '{$command}' not found.");
            }

            // Use dependency resolver to instantiate with constructor DI
            $instance = $this->resolver->instantiate($command);

            if (! $instance instanceof Command) {
                throw new \RuntimeException("Command class '{$command}' must extend Command.");
            }
        }

        return $instance;
    }
}
