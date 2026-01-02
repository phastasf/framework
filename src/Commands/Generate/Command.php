<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command as BaseCommand;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate a console command class.
 */
class Command extends BaseCommand
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'g:command';
    }

    public function getDescription(): string
    {
        return 'Generate a new console command class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Command name is required');
            $stdio->writeln('Usage: php console g:command CommandName');

            return 1;
        }

        // Normalize name
        $name = ucfirst($name);
        if (! str_ends_with($name, 'Command')) {
            $name .= 'Command';
        }

        // Determine namespace and path from config
        $namespace = $this->config->get('app.commands.namespace', 'App\\Commands');
        $basePath = $this->config->get('app.commands.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/app/Commands';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Command {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/command.stub';
        if (! file_exists($stubPath)) {
            $stdio->error("Stub file not found: {$stubPath}");

            return 1;
        }

        $stub = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $name],
            $stub
        );

        // Write file
        file_put_contents($filePath, $content);

        $stdio->info("Command {$name} created successfully at {$filePath}");

        return 0;
    }
}
