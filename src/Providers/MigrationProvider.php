<?php

declare(strict_types=1);

namespace Phast\Providers;

use Databoss\ConnectionInterface;
use Katora\Container;
use Katora\ServiceProviderInterface;
use Kram\MigrationManager;

/**
 * Migration service provider.
 */
class MigrationProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register migration manager
        $container->set('migration.manager', $container->share(function (Container $c) {
            $connection = $c->get(ConnectionInterface::class);
            $config = $c->get('config');
            $basePath = $config->get('app.base_path');
            $migrationsPath = $config->get('database.migrations', $basePath.'/database/migrations');

            // Handle relative paths
            if (is_string($migrationsPath) && ! str_starts_with($migrationsPath, '/')) {
                $migrationsPath = $basePath.'/'.ltrim($migrationsPath, '/');
            }

            return new MigrationManager($connection, $migrationsPath);
        }));

        $container->set(MigrationManager::class, fn (Container $c) => $c->get('migration.manager'));
    }
}
