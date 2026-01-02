<?php

declare(strict_types=1);

namespace Phast\Events;

use Clip\Command;
use Soochak\Event;

/**
 * Event dispatched before a console command is executed.
 */
class CommandStarting extends Event
{
    public function __construct(
        public readonly Command $command,
        public readonly array $arguments = [],
        public readonly array $options = []
    ) {}
}
