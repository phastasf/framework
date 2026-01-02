<?php

declare(strict_types=1);

namespace Phast\Providers;

use Godam\Cache;
use Godam\CacheItemPool;
use Godam\Store\FileSystemStore;
use Godam\Store\MemcacheStore;
use Godam\Store\MemoryStore;
use Godam\Store\PredisStore;
use Godam\Store\RedisStore;
use Katora\Container;
use Katora\ServiceProviderInterface;
use Kunfig\ConfigInterface;
use Predis\Client;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache service provider.
 */
class CacheProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register cache store
        $container->set('cache.store', $container->share(function (Container $c) {
            $config = $c->get('config');
            $driver = $config->get('cache.driver', 'file');

            return match ($driver) {
                'file' => $this->createFileStore($config),
                'memory' => new MemoryStore,
                'redis' => $this->createRedisStore($config),
                'predis' => $this->createPredisStore($config),
                'memcache' => $this->createMemcacheStore($config),
                default => new MemoryStore,
            };
        }));

        // Register PSR-16 simple cache
        $container->set('cache', $container->share(function (Container $c) {
            return new Cache($c->get('cache.store'));
        }));

        // Register PSR-6 cache item pool
        $container->set('cache.pool', $container->share(function (Container $c) {
            return new CacheItemPool($c->get('cache.store'));
        }));

        // Register interfaces
        $container->set(CacheInterface::class, fn (Container $c) => $c->get('cache'));
        $container->set(CacheItemPoolInterface::class, fn (Container $c) => $c->get('cache.pool'));
    }

    /**
     * Create FileSystemStore.
     */
    protected function createFileStore(ConfigInterface $config): FileSystemStore
    {
        $basePath = $config->get('app.base_path');
        $cachePath = $config->get('cache.file.path', $basePath.'/storage/cache/app');

        // Handle relative paths
        if (! str_starts_with($cachePath, '/')) {
            $cachePath = $basePath.'/'.ltrim($cachePath, '/');
        }

        return new FileSystemStore($cachePath);
    }

    /**
     * Create RedisStore (ext-redis).
     */
    protected function createRedisStore(ConfigInterface $config): MemoryStore|RedisStore
    {
        if (! extension_loaded('redis')) {
            // Fallback to memory if ext-redis is not available
            return new MemoryStore;
        }

        $redis = new \Redis;

        $host = $config->get('cache.redis.host', 'localhost');
        $port = $config->get('cache.redis.port', 6379);
        $password = $config->get('cache.redis.password', null);
        $database = $config->get('cache.redis.database', 0);

        $redis->connect($host, $port);

        if ($password !== null) {
            $redis->auth($password);
        }

        if ($database > 0) {
            $redis->select($database);
        }

        return new RedisStore($redis);
    }

    /**
     * Create PredisStore (predis/predis library).
     */
    protected function createPredisStore(ConfigInterface $config): MemoryStore|PredisStore
    {
        if (! class_exists(Client::class)) {
            // Fallback to memory if predis is not available
            return new MemoryStore;
        }

        $host = $config->get('cache.predis.host', 'localhost');
        $port = $config->get('cache.predis.port', 6379);
        $password = $config->get('cache.predis.password', null);
        $database = $config->get('cache.predis.database', 0);

        $parameters = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
        ];

        if ($password !== null) {
            $parameters['password'] = $password;
        }

        $client = new Client($parameters);

        return new PredisStore($client);
    }

    /**
     * Create MemcacheStore (ext-memcache).
     */
    protected function createMemcacheStore(ConfigInterface $config): MemoryStore|MemcacheStore
    {
        if (! extension_loaded('memcache')) {
            // Fallback to memory if ext-memcache is not available
            return new MemoryStore;
        }

        $memcache = new \Memcache;
        $host = $config->get('cache.memcache.host', 'localhost');
        $port = $config->get('cache.memcache.port', 11211);

        $memcache->connect($host, $port);

        return new MemcacheStore($memcache);
    }
}
