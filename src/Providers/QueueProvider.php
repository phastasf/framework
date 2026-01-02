<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Kunfig\ConfigInterface;
use Qatar\ElasticMQQueue;
use Qatar\Queue;
use Qatar\RedisQueue;
use RuntimeException;

/**
 * Queue service provider.
 */
class QueueProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        $container->set('queue', $container->share(function (Container $c) {
            $config = $c->get('config');
            $driver = $config->get('queue.driver', 'redis');
            $queueName = $config->get('queue.queue', 'default');

            return match ($driver) {
                'redis' => $this->createRedisQueue($config, $queueName),
                'sqs', 'elasticmq' => $this->createElasticMQQueue($config, $queueName),
                default => throw new RuntimeException("Unsupported queue driver: {$driver}"),
            };
        }));

        $container->set(Queue::class, fn (Container $c) => $c->get('queue'));
    }

    protected function createRedisQueue(ConfigInterface $config, string $queueName): RedisQueue
    {
        // Support connection string or individual parameters
        $connection = $config->get('queue.redis.connection', null);

        if ($connection === null) {
            $host = $config->get('queue.redis.host', '127.0.0.1');
            $port = $config->get('queue.redis.port', 6379);
            $password = $config->get('queue.redis.password', null);
            $database = $config->get('queue.redis.database', 0);
            $scheme = $config->get('queue.redis.scheme', 'tcp');

            $connection = "{$scheme}://{$host}:{$port}";

            if ($password !== null) {
                $connection = str_replace('://', "://:{$password}@", $connection);
            }

            if ($database > 0) {
                $connection .= "/{$database}";
            }
        }

        return new RedisQueue($connection, $queueName);
    }

    protected function createElasticMQQueue(ConfigInterface $config, string $queueName): ElasticMQQueue
    {
        // Try elasticmq config first, then sqs
        $version = $config->get('queue.elasticmq.version', $config->get('queue.sqs.version', 'latest'));
        $region = $config->get('queue.elasticmq.region', $config->get('queue.sqs.region', 'us-east-1'));
        $endpoint = $config->get('queue.elasticmq.endpoint', $config->get('queue.sqs.endpoint', null));
        $key = $config->get('queue.elasticmq.key', $config->get('queue.sqs.key', null));
        $secret = $config->get('queue.elasticmq.secret', $config->get('queue.sqs.secret', null));

        $awsConfig = [
            'version' => $version,
            'region' => $region,
        ];

        // Add endpoint for ElasticMQ (local development)
        if ($endpoint !== null) {
            $awsConfig['endpoint'] = $endpoint;
        }

        // Add credentials if provided
        if ($key !== null && $secret !== null) {
            $awsConfig['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        return new ElasticMQQueue($awsConfig, $queueName);
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
