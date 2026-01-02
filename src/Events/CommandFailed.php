<?php

declare(strict_types=1);

namespace Phast\Events;

use Clip\Command;
use Soochak\Event;
use Throwable;

/**
 * Event dispatched when a console command execution fails.
 */
class CommandFailed extends Event
{
    public function __construct(
        public readonly Command $command,
        public readonly Throwable $exception
    ) {}
}
