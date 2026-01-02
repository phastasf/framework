<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate a controller class.
 */
class Controller extends Command
{
    public function getName(): string
    {
        return 'g:controller';
    }

    public function getDescription(): string
    {
        return 'Generate a new controller class';
    }

    public function execute(Stdio $stdio, ConfigInterface $config): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Controller name is required');
            $stdio->writeln('Usage: php console g:controller ControllerName');

            return 1;
        }

        // Normalize name
        $name = ucfirst($name);
        if (! str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        // Determine namespace and path from config
        $namespace = $config->get('app.controllers.namespace', 'App\\Controllers');
        $basePath = $config->get('app.controllers.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $config->get('app.base_path');
            $basePath = $appBasePath.'/app/Controllers';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Controller {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/controller.stub';
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

        $stdio->info("Controller {$name} created successfully at {$filePath}");

        return 0;
    }
}
