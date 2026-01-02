<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate a model class.
 */
class Model extends Command
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'g:model';
    }

    public function getDescription(): string
    {
        return 'Generate a new model class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Model name is required');
            $stdio->writeln('Usage: php console g:model ModelName');

            return 1;
        }

        // Normalize name (singular, PascalCase)
        $name = ucfirst($name);

        // Determine namespace and path from config
        $namespace = $this->config->get('app.models.namespace', 'App\\Models');
        $basePath = $this->config->get('app.models.path');

        // If path is not configured, fall back to default
        if (empty($basePath)) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/app/Models';
        }

        // Handle relative paths
        if (! str_starts_with($basePath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $basePath = $appBasePath.'/'.ltrim($basePath, '/');
        }

        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Model {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Infer table name from model name (pluralize and lowercase)
        $table = $this->pluralize(strtolower($name));

        // Load stub
        $stubPath = __DIR__.'/../../../stubs/model.stub';
        if (! file_exists($stubPath)) {
            $stdio->error("Stub file not found: {$stubPath}");

            return 1;
        }

        $stub = file_get_contents($stubPath);
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{table}}'],
            [$namespace, $name, $table],
            $stub
        );

        // Write file
        file_put_contents($filePath, $content);

        $stdio->info("Model {$name} created successfully at {$filePath}");
        $stdio->writeln("  Table: {$table}");

        return 0;
    }

    /**
     * Simple pluralization helper.
     * Converts singular to plural (basic rules).
     */
    protected function pluralize(string $word): string
    {
        // Handle common irregular plurals
        $irregulars = [
            'user' => 'users',
            'person' => 'people',
            'child' => 'children',
            'man' => 'men',
            'woman' => 'women',
            'mouse' => 'mice',
            'goose' => 'geese',
            'foot' => 'feet',
            'tooth' => 'teeth',
        ];

        $lower = strtolower($word);
        if (isset($irregulars[$lower])) {
            return $irregulars[$lower];
        }

        // Basic pluralization rules
        if (str_ends_with($word, 'y') && ! in_array(substr($word, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($word, 0, -1).'ies';
        }

        $esEndings = ['s', 'sh', 'ch', 'x', 'z'];
        foreach ($esEndings as $ending) {
            if (str_ends_with($word, $ending)) {
                return $word.'es';
            }
        }

        if (str_ends_with($word, 'f')) {
            return substr($word, 0, -1).'ves';
        }

        if (str_ends_with($word, 'fe')) {
            return substr($word, 0, -2).'ves';
        }

        // Default: just add 's'
        return $word.'s';
    }
}
