<?php

declare(strict_types=1);

namespace Phast\Support;

use Clip\Command;
use Clip\Console as BaseConsole;
use Clip\Stdio;
use Katora\Container;
use Phast\Events\CommandExecuted;
use Phast\Events\CommandFailed;
use Phast\Events\CommandStarting;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Console implementation with container-based dependency injection.
 */
class Console extends BaseConsole
{
    protected DependencyResolver $resolver;

    protected ?EventDispatcherInterface $dispatcher = null;

    public function __construct(
        array $commands = [],
        private readonly ?Container $container = null
    ) {
        parent::__construct($commands);
        $this->resolver = ($this->container ?? new Container)->get(DependencyResolver::class);

        // Get event dispatcher if available
        if ($this->container !== null && $this->container->has(EventDispatcherInterface::class)) {
            $this->dispatcher = $this->container->get(EventDispatcherInterface::class);
        }
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
     * Run the console application with event dispatching.
     */
    public function run(?array $argv = null): int
    {
        $stdio = new Stdio($argv);

        $commandName = $stdio->getCommand();

        if (empty($commandName)) {
            return parent::run($argv);
        }

        $command = $this->getCommand($commandName);

        if ($command === null) {
            return parent::run($argv);
        }

        // Get arguments and options
        $arguments = $stdio->getArguments();
        array_shift($arguments); // Remove command name
        $options = $stdio->getOptions();

        // Dispatch command starting event
        if ($this->dispatcher !== null) {
            $this->dispatcher->dispatch(new CommandStarting($command, $arguments, $options));
        }

        try {
            // Execute command
            $exitCode = $command->execute($stdio);

            // Dispatch command executed event
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch(new CommandExecuted($command, $exitCode));
            }

            return $exitCode;
        } catch (\Throwable $e) {
            // Dispatch command failed event
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch(new CommandFailed($command, $e));
            }

            // Handle exception like parent does
            $stdio->error("Error: {$e->getMessage()}");

            return 1;
        }
    }
}
