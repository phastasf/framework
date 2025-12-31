<?php

declare(strict_types=1);

namespace Phast\Providers;

use Drishti\DailyFileBackend;
use Drishti\FileBackend;
use Drishti\JsonLogEntryFormatter;
use Drishti\LogEntryFormatterInterface;
use Drishti\Logger;
use Drishti\SimpleLogEntryFormatter;
use Drishti\StdioBackend;
use Katora\Container;
use Katora\ServiceProviderInterface;
use Kunfig\ConfigInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Logging service provider.
 */
class LoggingProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register logger
        $container->set('logger', $container->share(function (Container $c) {
            $config = $c->get('config');
            $backend = $config->get('logging.backend', 'file');

            // If backend is a string (comma-separated), convert to array
            if (is_string($backend)) {
                $backend = array_map('trim', explode(',', $backend));
            }

            // If backend is an array, trim each element and filter empty values
            if (is_array($backend)) {
                $backend = array_filter(array_map('trim', $backend));
                // If array is empty after filtering, default to 'file'
                if (empty($backend)) {
                    $backend = 'file';
                }
            }

            $formatter = $this->createFormatter($config, $c);

            // Create backend(s) based on configuration
            $backends = $this->createBackends($backend, $config, $formatter, $c);

            // If single backend, pass directly; if array, pass array
            if (is_array($backends) && count($backends) === 1) {
                return new Logger($backends[0]);
            }

            return new Logger($backends);
        }));

        // Register PSR-3 interface
        $container->set(LoggerInterface::class, fn (Container $c) => $c->get('logger'));
    }

    /**
     * Create formatter based on configuration.
     */
    protected function createFormatter(ConfigInterface $config, Container $container): ?LogEntryFormatterInterface
    {
        $formatterType = $config->get('logging.formatter', 'simple');

        // Get clock from container if available
        $clock = $container->has(ClockInterface::class)
            ? $container->get(ClockInterface::class)
            : null;

        return match ($formatterType) {
            'json' => new JsonLogEntryFormatter($clock),
            'simple' => new SimpleLogEntryFormatter($clock),
            default => new SimpleLogEntryFormatter($clock),
        };
    }

    /**
     * Create backend(s) based on configuration.
     */
    protected function createBackends(string|array $backend, ConfigInterface $config, ?LogEntryFormatterInterface $formatter, Container $container): array|object
    {
        // If backend is an array, create multiple backends
        if (is_array($backend)) {
            $backends = [];
            foreach ($backend as $backendType) {
                $backends[] = $this->createSingleBackend($backendType, $config, $formatter, $container);
            }

            return $backends;
        }

        // Single backend
        return $this->createSingleBackend($backend, $config, $formatter, $container);
    }

    /**
     * Create a single backend instance.
     */
    protected function createSingleBackend(string $backend, ConfigInterface $config, ?LogEntryFormatterInterface $formatter, Container $container): object
    {
        $clock = $container->has(ClockInterface::class)
            ? $container->get(ClockInterface::class)
            : null;

        return match ($backend) {
            'stdio' => $this->createStdioBackend($config, $formatter, $clock),
            'file' => $this->createFileBackend($config, $formatter, $clock),
            'daily' => $this->createDailyFileBackend($config, $formatter, $clock),
            default => $this->createFileBackend($config, $formatter, $clock),
        };
    }

    /**
     * Create StdioBackend.
     */
    protected function createStdioBackend(ConfigInterface $config, ?LogEntryFormatterInterface $formatter, ?ClockInterface $clock): object
    {
        $stream = $config->get('logging.stdio.stream', 'stdout');

        return match ($stream) {
            'stderr' => StdioBackend::stderr($formatter, $clock),
            'stdout' => StdioBackend::stdout($formatter, $clock),
            default => StdioBackend::stdout($formatter, $clock),
        };
    }

    /**
     * Create FileBackend.
     */
    protected function createFileBackend(ConfigInterface $config, ?LogEntryFormatterInterface $formatter, ?ClockInterface $clock): object
    {
        $basePath = $config->get('app.base_path');
        $logPath = $config->get('logging.file.path', $basePath.'/storage/logs/app.log');

        // Handle relative paths
        if (! str_starts_with($logPath, '/')) {
            $logPath = $basePath.'/'.ltrim($logPath, '/');
        }

        // Ensure log directory exists
        $logDir = dirname($logPath);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        return new FileBackend($logPath, $formatter, $clock);
    }

    /**
     * Create DailyFileBackend.
     */
    protected function createDailyFileBackend(ConfigInterface $config, ?LogEntryFormatterInterface $formatter, ?ClockInterface $clock): object
    {
        $basePath = $config->get('app.base_path');
        $logPath = $config->get('logging.daily.path', $basePath.'/storage/logs/app');

        // Handle relative paths
        if (! str_starts_with($logPath, '/')) {
            $logPath = $basePath.'/'.ltrim($logPath, '/');
        }

        // Ensure log directory exists
        if (! is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        return new DailyFileBackend($logPath, $formatter, $clock);
    }
}
