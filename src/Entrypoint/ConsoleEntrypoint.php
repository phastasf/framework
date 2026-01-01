<?php

declare(strict_types=1);

namespace Phast\Entrypoint;

use Clip\Command as BaseCommand;
use Clip\Console;
use Katora\Container;
use Phast\Commands\Generate\Command as GenerateCommand;
use Phast\Commands\Generate\Controller;
use Phast\Commands\Generate\Job;
use Phast\Commands\Generate\Migration;
use Phast\Commands\Generate\Model;
use Phast\Commands\Migrate\Down;
use Phast\Commands\Migrate\Up;
use Phast\Commands\Serve;
use Phast\Commands\Worker;

/**
 * Console entrypoint for Phast framework.
 */
class ConsoleEntrypoint
{
    protected Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? new Container;
    }

    /**
     * Create and configure the console application.
     */
    public function create(): Console
    {
        // Register framework commands
        $commands = [
            Controller::class,
            Migration::class,
            Job::class,
            Model::class,
            GenerateCommand::class,
            Up::class,
            Down::class,
            Serve::class,
            Worker::class,
        ];

        // Discover and load commands from app namespace
        $appCommands = $this->discoverCommands();
        $commands = array_merge($commands, $appCommands);

        return new Console($commands, $this->container);
    }

    /**
     * Discover commands from the app namespace.
     *
     * @return array<string>
     */
    protected function discoverCommands(): array
    {
        $discovered = [];

        $config = $this->container->get('config');
        $namespace = $config->get('app.commands.namespace', 'App\\Commands');
        $basePath = $config->get('app.commands.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $config->get('app.base_path');
            $basePath = $appBasePath.'/app/Commands';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        // Check if directory exists
        if (! is_dir($basePath)) {
            return $discovered;
        }

        // Scan directory for PHP files
        $files = glob($basePath.'/*.php');

        foreach ($files as $file) {
            $className = $namespace.'\\'.basename($file, '.php');

            // Check if class exists and extends BaseCommand
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);

                if ($reflection->isSubclassOf(BaseCommand::class) && ! $reflection->isAbstract()) {
                    $discovered[] = $className;
                }
            }
        }

        return $discovered;
    }

    /**
     * Run the console application.
     */
    public function run(): int
    {
        return $this->create()->run();
    }
}
