<?php

declare(strict_types=1);

namespace Phast\Commands\Seed;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;
use Phast\Database\SeederInterface;
use Phast\Support\DependencyResolver;

/**
 * Command to run database seeders.
 * Executes only the seeders listed in config database.seed.
 * Seeders are instantiated via DependencyResolver so constructor dependencies are resolved from the container.
 */
class Run extends Command
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly DependencyResolver $resolver
    ) {}

    public function getName(): string
    {
        return 'seed';
    }

    public function getDescription(): string
    {
        return 'Run database seeders (configurable via database.seed)';
    }

    public function execute(Stdio $stdio): int
    {
        $seedList = $this->config->get('database.seed', []);

        if ($seedList instanceof ConfigInterface) {
            $seedList = $seedList->all();
        }

        if (empty($seedList) || ! is_array($seedList)) {
            $stdio->info('No seeders configured. Add class names to config database.seed.');
            $stdio->writeln();

            return 0;
        }

        $seedersPath = $this->resolveSeedersPath();

        $stdio->writeln();
        $stdio->info('Running seeders...');
        $stdio->writeln();

        foreach ($seedList as $className) {
            $className = is_string($className) ? trim($className) : '';

            if ($className === '') {
                continue;
            }

            try {
                $seeder = $this->resolveSeeder($className, $seedersPath);

                if (! $seeder instanceof SeederInterface) {
                    $stdio->error("Seeder {$className} does not implement ".SeederInterface::class);

                    return 1;
                }

                $seeder->run();
                $stdio->writeln("  - {$className}");
            } catch (\Throwable $e) {
                $stdio->error("Seeder failed ({$className}): ".$e->getMessage());
                $stdio->writeln($e->getTraceAsString());

                return 1;
            }
        }

        $stdio->writeln();
        $stdio->info('Seeding completed successfully!');
        $stdio->writeln();

        return 0;
    }

    /**
     * Resolve seeder instance using DependencyResolver so constructor dependencies are injected from the container.
     * For short names, the seeder file is loaded first so the class is available.
     */
    private function resolveSeeder(string $className, string $seedersPath): object
    {
        if (! str_contains($className, '\\')) {
            $filePath = $seedersPath.'/'.$className.'.php';

            if (! file_exists($filePath)) {
                throw new \RuntimeException("Seeder file not found: {$filePath}");
            }

            require_once $filePath;

            if (! class_exists($className, false)) {
                throw new \RuntimeException("Class {$className} not found in {$filePath}");
            }
        }

        return $this->resolver->instantiate($className);
    }

    private function resolveSeedersPath(): string
    {
        $basePath = $this->config->get('app.base_path');
        $seedersPath = $this->config->get('database.seeders', $basePath.'/database/seeders');

        if (is_string($seedersPath) && ! str_starts_with($seedersPath, '/')) {
            $seedersPath = $basePath.'/'.ltrim($seedersPath, '/');
        }

        return $seedersPath;
    }
}
