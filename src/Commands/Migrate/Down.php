<?php

declare(strict_types=1);

namespace Phast\Commands\Migrate;

use Clip\Command;
use Clip\Stdio;
use Kram\MigrationManager;

/**
 * Command to rollback database migrations.
 */
class Down extends Command
{
    public function __construct(
        private readonly MigrationManager $manager
    ) {}

    public function getName(): string
    {
        return 'm:down';
    }

    public function getDescription(): string
    {
        return 'Rollback the last database migration';
    }

    public function execute(Stdio $stdio): int
    {

        // Get optional count parameter (default: 1)
        $countArg = $stdio->getArgument(0);
        $count = ! empty($countArg) ? (int) $countArg : 1;
        if ($count < 1) {
            $count = 1;
        }

        $stdio->writeln();
        $stdio->info("Rolling back {$count} migration(s)...");
        $stdio->writeln();

        try {
            $result = $this->manager->rollbackTo(null, $count);

            if ($result->success) {
                if (empty($result->rolledBack)) {
                    $stdio->info('No migrations to rollback.');
                } else {
                    $stdio->info('Rollback completed successfully!');
                    $stdio->writeln();
                    $stdio->writeln('Rolled back migrations:');
                    foreach ($result->rolledBack as $version) {
                        $stdio->writeln("  - {$version}");
                    }
                }
            } else {
                $stdio->error('Rollback failed: '.$result->message);

                return 1;
            }
        } catch (\Throwable $e) {
            $stdio->error('Rollback error: '.$e->getMessage());
            $stdio->writeln($e->getTraceAsString());

            return 1;
        }

        $stdio->writeln();

        return 0;
    }
}
