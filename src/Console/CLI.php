<?php

declare(strict_types=1);

namespace Phast\Console;

use Clip\Command;
use Clip\Console;
use Clip\Stdio;
use Katora\Container;
use Phast\Support\DependencyResolver;

/**
 * Console implementation with container-based dependency injection.
 */
class CLI extends Console
{
    protected Container $container;

    protected DependencyResolver $resolver;

    public function __construct(array $commands = [], ?Container $container = null)
    {
        parent::__construct($commands);
        $this->container = $container ?? new Container;
        $this->resolver = new DependencyResolver($this->container);
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

    /**
     * Runs the console application.
     * Overrides parent to inject Stdio and other dependencies into execute method via dependency injection.
     *
     * @param  array<string>|null  $argv  Command line arguments (defaults to $_SERVER['argv'])
     * @return int The exit code
     */
    public function run(?array $argv = null): int
    {
        $stdio = new Stdio($argv);

        $commandName = $stdio->getCommand();

        if (empty($commandName)) {
            $this->listCommands($stdio);

            return 0;
        }

        $command = $this->getCommand($commandName);

        if ($command === null) {
            $stdio->error("Command '{$commandName}' not found.");
            $stdio->writeln('');
            $this->listCommands($stdio);

            return 1;
        }

        try {
            // Resolve execute method arguments (Stdio and other dependencies via DI)
            $args = $this->resolver->resolveMethodArguments(
                [$command, 'execute'],
                ['stdio' => $stdio]
            );

            return $command->execute(...$args);
        } catch (\Throwable $e) {
            $stdio->error("Error: {$e->getMessage()}");

            return 1;
        }
    }
}
