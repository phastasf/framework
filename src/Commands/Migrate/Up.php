<?php

declare(strict_types=1);

namespace Phast\Commands\Migrate;

use Clip\Command;
use Clip\Stdio;
use Kram\MigrationManager;

/**
 * Command to run database migrations up.
 */
class Up extends Command
{
    public function __construct(
        private readonly ?MigrationManager $manager = null
    ) {}

    public function getName(): string
    {
        return 'm:up';
    }

    public function getDescription(): string
    {
        return 'Run pending database migrations';
    }

    public function execute(Stdio $stdio): int
    {

        $stdio->writeln();
        $stdio->info('Running migrations...');
        $stdio->writeln();

        try {
            $result = $this->manager->migrate();

            if ($result->success) {
                if (empty($result->executed)) {
                    $stdio->info('No pending migrations.');
                } else {
                    $stdio->info('Migrations completed successfully!');
                    $stdio->writeln();
                    $stdio->writeln('Executed migrations:');
                    foreach ($result->executed as $version) {
                        $stdio->writeln("  - {$version}");
                    }
                }
            } else {
                $stdio->error('Migration failed: '.$result->message);

                return 1;
            }
        } catch (\Throwable $e) {
            $stdio->error('Migration error: '.$e->getMessage());
            $stdio->writeln($e->getTraceAsString());

            return 1;
        }

        $stdio->writeln();

        return 0;
    }
}
