<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate an event class.
 */
class Event extends Command
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'g:event';
    }

    public function getDescription(): string
    {
        return 'Generate a new event class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Event name is required');
            $stdio->writeln('Usage: php console g:event EventName');

            return 1;
        }

        // Normalize name (PascalCase)
        $name = ucfirst($name);
        if (! str_ends_with($name, 'Event')) {
            $name .= 'Event';
        }

        // Determine namespace and path from config
        $namespace = $this->config->get('app.events.namespace', 'App\\Events');
        $basePath = $this->config->get('app.events.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/app/Events';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Event {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Generate description from class name
        $description = $this->generateDescription($name);

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/event.stub';
        if (! file_exists($stubPath)) {
            $stdio->error("Stub file not found: {$stubPath}");

            return 1;
        }

        $stub = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{description}}'],
            [$namespace, $name, $description],
            $stub
        );

        // Write file
        file_put_contents($filePath, $content);

        $stdio->info("Event {$name} created successfully at {$filePath}");

        return 0;
    }

    /**
     * Generate a description from the event class name.
     */
    protected function generateDescription(string $className): string
    {
        // Remove "Event" suffix if present
        $name = str_replace('Event', '', $className);

        // Convert PascalCase to a readable description
        $words = preg_split('/(?=[A-Z])/', $name);
        $words = array_filter($words); // Remove empty strings
        $words = array_map('strtolower', $words);

        return implode(' ', $words);
    }
}
