<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;
use Kunfig\Config;
use Kunfig\ConfigInterface;

/**
 * Configuration service provider.
 */
class ConfigProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register config
        $container->set('config', $container->share(function () {
            // Try to load from cache if it exists (regardless of debug mode)
            $cached = $this->loadCachedConfig();
            if ($cached !== null) {
                $config = new Config($cached);
                // Ensure base_path is set if BASE_PATH is defined
                if (! $config->has('app.base_path') && defined('BASE_PATH')) {
                    $config->set('app.base_path', BASE_PATH);
                }

                return $config;
            }

            // Cache doesn't exist, load fresh
            $config = $this->loadConfigFiles();
            $basePath = $config->get('app.base_path');

            // If not in debug mode and cache doesn't exist, create cache
            if (! empty($basePath)) {
                $debug = $config->get('app.debug', true);
                if (! $debug) {
                    $this->cacheConfig($config->all(), $basePath);
                }
            }

            return $config;
        }));
        $container->set(ConfigInterface::class, fn () => $container->get('config'));
    }

    /**
     * Load all config files and return Config instance.
     */
    protected function loadConfigFiles(): Config
    {
        // Load default configs from package root config/
        $defaultsPath = __DIR__.'/../../config';
        $defaults = $this->loadConfigFilesFromPath($defaultsPath);
        $config = new Config($defaults);

        // Get base path (from defaults or BASE_PATH constant)
        $basePath = $config->get('app.base_path');
        if (empty($basePath) && defined('BASE_PATH')) {
            $basePath = BASE_PATH;
            $config->set('app.base_path', $basePath);
        }

        // Load and merge overrides from project root config/
        if (! empty($basePath)) {
            $overridesPath = $basePath.'/config';
            $overrides = $this->loadConfigFilesFromPath($overridesPath);
            $config->mix(new Config($overrides));
        }

        return $config;
    }

    /**
     * Load cached config from disk.
     *
     * @return array<string, mixed>|null
     */
    protected function loadCachedConfig(): ?array
    {
        if (! defined('BASE_PATH')) {
            return null;
        }

        $cacheFile = BASE_PATH.'/storage/cache/config.php';

        if (! file_exists($cacheFile)) {
            return null;
        }

        try {
            $cached = require $cacheFile;
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Invalid cache file, will regenerate
        }

        return null;
    }

    /**
     * Cache config to disk.
     *
     * @param  array<string, mixed>  $config
     */
    protected function cacheConfig(array $config, string $basePath): void
    {
        $cacheDir = $basePath.'/storage/cache';
        $cacheFile = $cacheDir.'/config.php';

        // Ensure cache directory exists
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Dump config to cache file (overwrite if exists)
        try {
            $exported = var_export($config, true);
            file_put_contents($cacheFile, "<?php\n\nreturn {$exported};\n");
        } catch (\Throwable $e) {
            // Silently fail if caching fails
        }
    }

    /**
     * Load all config files from a directory.
     *
     * @param  string  $path  Directory path
     * @return array<string, array> Array of config arrays keyed by filename
     */
    protected function loadConfigFilesFromPath(string $path): array
    {
        $configs = [];

        if (is_dir($path)) {
            $files = glob($path.'/*.php');
            foreach ($files as $file) {
                $key = basename($file, '.php');
                $values = require $file;
                if (is_array($values)) {
                    $configs[$key] = $values;
                }
            }
        }

        return $configs;
    }
}
