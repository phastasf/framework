<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;

/**
 * Command to generate a migration class.
 */
class Migration extends Command
{
    public function getName(): string
    {
        return 'g:migration';
    }

    public function getDescription(): string
    {
        return 'Generate a new migration class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Migration name is required');
            $stdio->writeln('Usage: php console g:migration migration_name');

            return 1;
        }

        // Generate version timestamp
        $version = date('YmdHis');
        $className = $this->toPascalCase($name);
        $fileName = "{$version}_{$name}.php";

        // Determine path from config
        $config = $this->get('config');
        $migrationsPath = $config->get('database.migrations');

        // If not configured, fall back to default
        if (empty($migrationsPath)) {
            $appBasePath = $config->get('app.base_path');
            $migrationsPath = $appBasePath.'/database/migrations';
        }

        // Handle relative paths
        if (! str_starts_with($migrationsPath, '/')) {
            $appBasePath = $config->get('app.base_path');
            $migrationsPath = $appBasePath.'/'.ltrim($migrationsPath, '/');
        }

        $filePath = $migrationsPath.'/'.$fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Migration {$fileName} already exists");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/migration.stub';
        if (! file_exists($stubPath)) {
            $stdio->error("Stub file not found: {$stubPath}");

            return 1;
        }

        $stub = file_get_contents($stubPath);
        $content = str_replace(
            ['{{class}}', '{{version}}'],
            [$className, $version],
            $stub
        );

        // Write file
        file_put_contents($filePath, $content);

        $stdio->info("Migration {$fileName} created successfully at {$filePath}");

        return 0;
    }

    /**
     * Convert snake_case or kebab-case to PascalCase.
     */
    protected function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);

        return str_replace(' ', '', $string);
    }
}
