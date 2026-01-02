<?php

declare(strict_types=1);

namespace Phast\Commands;

use Clip\Command;
use Clip\Stdio;

/**
 * Command to clear cached files.
 */
class ClearCache extends Command
{
    public function getName(): string
    {
        return 'cache:clear';
    }

    public function getDescription(): string
    {
        return 'Clear cached config and routes files';
    }

    public function execute(Stdio $stdio): int
    {
        $config = $this->get('config');
        $basePath = $config->get('app.base_path');

        if (empty($basePath)) {
            $stdio->error('Base path not configured');

            return 1;
        }

        $cacheDir = $basePath.'/storage/cache';
        $configCache = $cacheDir.'/config.php';
        $routesCache = $cacheDir.'/routes.php';

        $cleared = false;

        // Delete config cache if exists
        if (file_exists($configCache)) {
            if (unlink($configCache)) {
                $stdio->info('Cleared config cache');
                $cleared = true;
            } else {
                $stdio->error("Failed to delete config cache: {$configCache}");

                return 1;
            }
        }

        // Delete routes cache if exists
        if (file_exists($routesCache)) {
            if (unlink($routesCache)) {
                $stdio->info('Cleared routes cache');
                $cleared = true;
            } else {
                $stdio->error("Failed to delete routes cache: {$routesCache}");

                return 1;
            }
        }

        if (! $cleared) {
            $stdio->writeln('No cache files found');

            return 0;
        }

        $stdio->writeln('Cache cleared successfully');

        return 0;
    }
}
