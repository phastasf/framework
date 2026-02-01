<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to generate a seeder class.
 */
class Seeder extends Command
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'g:seeder';
    }

    public function getDescription(): string
    {
        return 'Generate a new seeder class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Seeder name is required');
            $stdio->writeln('Usage: php console g:seeder SeederName');

            return 1;
        }

        $className = $this->toPascalCase($name);
        if (! str_ends_with($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $fileName = $className.'.php';

        // Determine path from config
        $seedersPath = $this->config->get('database.seeders');

        if (empty($seedersPath)) {
            $appBasePath = $this->config->get('app.base_path');
            $seedersPath = $appBasePath.'/database/seeders';
        }

        if (is_string($seedersPath) && ! str_starts_with($seedersPath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $seedersPath = $appBasePath.'/'.ltrim($seedersPath, '/');
        }

        $filePath = $seedersPath.'/'.$fileName;

        if (file_exists($filePath)) {
            $stdio->error("Seeder {$fileName} already exists");

            return 1;
        }

        if (! is_dir($seedersPath)) {
            mkdir($seedersPath, 0755, true);
        }

        $stubPath = __DIR__.'/../../../stubs/seeder.stub';
        if (! file_exists($stubPath)) {
            $stdio->error("Stub file not found: {$stubPath}");

            return 1;
        }

        $stub = file_get_contents($stubPath);
        $content = str_replace('{{class}}', $className, $stub);

        file_put_contents($filePath, $content);

        $stdio->info("Seeder {$fileName} created successfully at {$filePath}");
        $stdio->writeln('Add "'.$className.'" to config database.seed to run it with the seed command.');

        return 0;
    }

    protected function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);

        return str_replace(' ', '', $string);
    }
}
