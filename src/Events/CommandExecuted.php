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
        public readonly Command $command,
        public readonly int $exitCode
    ) {}
}
