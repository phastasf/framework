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
        Command $command,
        Throwable $exception
    ) {
        parent::__construct(self::class, [
            'command' => $command,
            'exception' => $exception,
        ]);
    }

    public function getCommand(): Command
    {
        return $this->getParam('command');
    }

    public function getException(): Throwable
    {
        return $this->getParam('exception');
    }
}
