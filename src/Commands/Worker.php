<?php

declare(strict_types=1);

namespace Phast\Commands;

use Clip\Command;
use Clip\Stdio;
use Qatar\Queue;
use Qatar\Worker as QueueWorker;
use Qatar\WorkerOptions;

/**
 * Command to run queue workers.
 */
class Worker extends Command
{
    public function getName(): string
    {
        return 'worker';
    }

    public function getDescription(): string
    {
        return 'Run queue worker to process jobs';
    }

    public function execute(Stdio $stdio, Queue $queue): int
    {

        // Parse worker options from command line
        $sleep = (int) $stdio->getOption('sleep', '3');
        $maxJobs = $stdio->getOption('max-jobs') ? (int) $stdio->getOption('max-jobs') : null;
        $maxTime = $stdio->getOption('max-time') ? (int) $stdio->getOption('max-time') : null;
        $memory = (int) $stdio->getOption('memory', '128');
        $stopOnEmpty = $stdio->hasOption('stop-on-empty');

        $options = new WorkerOptions(
            sleep: $sleep,
            maxJobs: $maxJobs,
            maxTime: $maxTime,
            memory: $memory,
            stopOnEmpty: $stopOnEmpty
        );

        $worker = new QueueWorker($queue, $options);

        $stdio->writeln();
        $stdio->info('Queue worker started');
        $stdio->writeln(" Sleep: {$sleep}s");
        if ($maxJobs !== null) {
            $stdio->writeln(" Max jobs: {$maxJobs}");
        }
        if ($maxTime !== null) {
            $stdio->writeln(" Max time: {$maxTime}s");
        }
        $stdio->writeln(" Memory limit: {$memory}MB");
        $stdio->writeln();
        $stdio->writeln('Press Ctrl+C to stop the worker');
        $stdio->writeln();

        $worker->work();

        return 0;
    }
}
