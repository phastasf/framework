<?php

declare(strict_types=1);

namespace Phast\Events;

use Clip\Command;
use Soochak\Event;

/**
 * Event dispatched after a console command is successfully executed.
 */
class CommandExecuted extends Event
{
    public function __construct(
        Command $command,
        int $exitCode
    ) {
        parent::__construct(self::class, [
            'command' => $command,
            'exitCode' => $exitCode,
        ]);
    }

    public function getCommand(): Command
    {
        return $this->getParam('command');
    }

    public function getExitCode(): int
    {
        return $this->getParam('exitCode');
    }
}
