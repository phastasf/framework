<?php

declare(strict_types=1);

namespace Phast\Support;

use Katora\Container;
use Qatar\Job;
use Qatar\Queue;
use Qatar\Worker as BaseWorker;
use Qatar\WorkerOptions;

/**
 * Worker implementation with dependency injection support.
 */
class Worker extends BaseWorker
{
    protected DependencyResolver $resolver;

    public function __construct(
        Queue $queue,
        WorkerOptions $options = new WorkerOptions,
        ?Container $container = null
    ) {
        parent::__construct($queue, $options);
        $this->resolver = ($container ?? new Container)->get(DependencyResolver::class);
    }

    /**
     * Resolve a job handler instance from its class name with dependency injection.
     *
     * @param  string  $jobClass  Fully qualified job class name.
     * @param  array<string, mixed>  $payload  Job payload data.
     * @return Job The job handler instance.
     */
    protected function resolveJob(string $jobClass, array $payload): Job
    {
        if (! class_exists($jobClass)) {
            throw new \RuntimeException("Job class '{$jobClass}' does not exist.");
        }

        // Use dependency resolver to instantiate with constructor DI
        $job = $this->resolver->instantiate($jobClass);

        if (! $job instanceof Job) {
            throw new \RuntimeException("Job class '{$jobClass}' must implement Qatar\\Job interface.");
        }

        return $job;
    }
}
