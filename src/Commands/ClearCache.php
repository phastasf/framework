<?php

declare(strict_types=1);

namespace Phast\Commands;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Command to clear cached files.
 */
class ClearCache extends Command
{
    public function getName(): string
    {
        return 'uncache';
    }

    public function getDescription(): string
    {
        return 'Clear cached config, routes, and application cache';
    }

    public function execute(Stdio $stdio, ConfigInterface $config, ?CacheInterface $cache = null): int
    {
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

        // Clear application cache if available
        if ($cache !== null) {
            if ($cache->clear()) {
                $stdio->info('Cleared application cache');
                $cleared = true;
            } else {
                $stdio->warn('Failed to clear application cache');
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
