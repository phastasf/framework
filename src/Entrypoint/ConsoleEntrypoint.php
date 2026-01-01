<?php

declare(strict_types=1);

namespace Phast\Entrypoint;

use Clip\Console;
use Katora\Container;
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
            Up::class,
            Down::class,
            Serve::class,
            Worker::class,
        ];

        return new Console($commands, $this->container);
    }

    /**
     * Run the console application.
     */
    public function run(): int
    {
        return $this->create()->run();
    }
}
