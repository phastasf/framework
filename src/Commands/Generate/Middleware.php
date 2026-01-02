<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate a middleware class.
 */
class Middleware extends Command
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'g:middleware';
    }

    public function getDescription(): string
    {
        return 'Generate a new middleware class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Middleware name is required');
            $stdio->writeln('Usage: php console g:middleware MiddlewareName');

            return 1;
        }

        // Normalize name (PascalCase)
        $name = ucfirst($name);
        if (! str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        // Determine namespace and path from config
        $namespace = $this->config->get('app.middleware.namespace', 'App\\Middleware');
        $basePath = $this->config->get('app.middleware.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/app/Middleware';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Middleware {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Generate description from class name
        $description = $this->generateDescription($name);

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/middleware.stub';
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

        $stdio->info("Middleware {$name} created successfully at {$filePath}");

        return 0;
    }

    /**
     * Generate a description from the middleware class name.
     */
    protected function generateDescription(string $className): string
    {
        // Remove "Middleware" suffix if present
        $name = str_replace('Middleware', '', $className);

        // Convert PascalCase to a readable description
        $words = preg_split('/(?=[A-Z])/', $name);
        $words = array_filter($words); // Remove empty strings
        $words = array_map('strtolower', $words);

        return implode(' ', $words);
    }
}
