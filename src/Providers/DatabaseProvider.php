<?php

declare(strict_types=1);

namespace Phast\Providers;

use Databoss\Connection;
use Databoss\ConnectionInterface;
use Datum\Model;
use Katora\Container;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Database service provider.
 */
class DatabaseProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register database connection
        $container->set('database.connection', $container->share(function (Container $c) {
            $config = $c->get('config');
            $driver = $config->get('database.driver', 'mysql');

            $options = [
                Connection::OPT_DRIVER => $driver,
            ];

            // SQLite only needs database path
            if ($driver === 'sqlite') {
                $basePath = $config->get('app.base_path');
                $database = $config->get("database.{$driver}.database", $basePath.'/database/database.sqlite');

                // Handle relative paths
                if (! str_starts_with($database, '/') && $database !== ':memory:') {
                    $database = $basePath.'/'.ltrim($database, '/');
                }

                $options[Connection::OPT_DATABASE] = $database;
            } else {
                // MySQL, PostgreSQL, SQL Server need host, port, database, username, password
                $options[Connection::OPT_HOST] = $config->get("database.{$driver}.host", 'localhost');
                $options[Connection::OPT_PORT] = $config->get("database.{$driver}.port", $driver === 'pgsql' ? 5432 : ($driver === 'sqlsrv' ? 1433 : 3306));
                $options[Connection::OPT_DATABASE] = $config->get("database.{$driver}.database", '');
                $options[Connection::OPT_USERNAME] = $config->get("database.{$driver}.username", $driver === 'pgsql' ? 'postgres' : ($driver === 'sqlsrv' ? 'sa' : 'root'));
                $options[Connection::OPT_PASSWORD] = $config->get("database.{$driver}.password", '');

                // Charset for MySQL and PostgreSQL
                if (in_array($driver, ['mysql', 'pgsql'])) {
                    $options[Connection::OPT_CHARSET] = $config->get("database.{$driver}.charset", $driver === 'pgsql' ? 'utf8' : 'utf8mb4');
                }

                // Prefix for MySQL and PostgreSQL
                $prefix = $config->get("database.{$driver}.prefix", null);
                if ($prefix !== null && in_array($driver, ['mysql', 'pgsql'])) {
                    $options[Connection::OPT_PREFIX] = $prefix;
                }

                // TrustServerCertificate for SQL Server
                if ($driver === 'sqlsrv') {
                    $trustCert = $config->get("database.{$driver}.trust_server_certificate", null);
                    if ($trustCert !== null) {
                        $options[Connection::OPT_TRUST_SERVER_CERTIFICATE] = (bool) $trustCert;
                    }
                }
            }

            return new Connection($options);
        }));

        // Register interface
        $container->set(ConnectionInterface::class, fn (Container $c) => $c->get('database.connection'));
    }

    public function init(Container $container): void
    {
        // Set connection for Datum models
        Model::connect(fn () => $container->get(ConnectionInterface::class));

        // Set event dispatcher for Datum models if available
        if ($container->has(EventDispatcherInterface::class)) {
            Model::dispatcher(fn () => $container->get(EventDispatcherInterface::class));
        }
    }
}
