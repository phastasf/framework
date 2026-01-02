<?php

declare(strict_types=1);

namespace Phast\Commands;

use Clip\Command;
use Clip\Stdio;
use Prayog\Config;
use Prayog\Repl;

/**
 * Command to start an interactive PHP shell (REPL).
 */
class Shell extends Command
{
    public function getName(): string
    {
        return 'shell';
    }

    public function getDescription(): string
    {
        return 'Start an interactive PHP shell (REPL)';
    }

    public function execute(Stdio $stdio): int
    {
        // Check if container is available
        if ($this->container === null) {
            $stdio->error('Container is not available.');

            return 1;
        }

        // Create REPL configuration
        $config = new Config(
            prompt: 'phast> ',
            colorOutput: true,
            welcomeMessage: "Phast Framework Interactive Shell\nPHP ".PHP_VERSION."\nType 'exit' or press Ctrl+D to quit.\n",
        );

        // Create and configure the REPL
        $repl = new Repl($config);

        // Make container available in the REPL
        $repl->setVariable('container', $this->container);

        $stdio->writeln();
        $stdio->info('Starting interactive shell...');
        $stdio->writeln('The $container variable is available.');
        $stdio->writeln();

        // Start the REPL
        $repl->start();

        return 0;
    }
}
