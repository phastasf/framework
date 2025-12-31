<?php

declare(strict_types=1);

namespace Phast\Commands\Generate;

use Clip\Command;
use Clip\Stdio;

/**
 * Command to generate a job class.
 */
class Job extends Command
{
    public function getName(): string
    {
        return 'g:job';
    }

    public function getDescription(): string
    {
        return 'Generate a new job class';
    }

    public function execute(Stdio $stdio): int
    {
        $name = $stdio->getArgument(0);

        if (empty($name)) {
            $stdio->error('Job name is required');
            $stdio->writeln('Usage: php console g:job JobName');

            return 1;
        }

        // Normalize name
        $name = ucfirst($name);
        if (! str_ends_with($name, 'Job')) {
            $name .= 'Job';
        }

        // Determine namespace and path
        $namespace = 'App\\Jobs';
        $config = $this->get('config');
        $appBasePath = $config->get('app.base_path');
        $basePath = $appBasePath.'/app/Jobs';
        $filePath = $basePath.'/'.$name.'.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            $stdio->error("Job {$name} already exists at {$filePath}");

            return 1;
        }

        // Ensure directory exists
        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Load stub
        $stubPath = $appBasePath.'/stubs/job.stub';
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

        $stdio->info("Job {$name} created successfully at {$filePath}");

        return 0;
    }
}
