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
        Command $command,
        array $arguments = [],
        array $options = []
    ) {
        parent::__construct(self::class, [
            'command' => $command,
            'arguments' => $arguments,
            'options' => $options,
        ]);
    }

    public function getCommand(): Command
    {
        return $this->getParam('command');
    }

    public function getArguments(): array
    {
        return $this->getParam('arguments') ?? [];
    }

    public function getOptions(): array
    {
        return $this->getParam('options') ?? [];
    }
}
