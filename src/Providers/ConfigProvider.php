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
        $config = new Config;

        // First, load default configs from package root config/
        $defaults = $this->loadConfigFiles(__DIR__.'/../../config');
        foreach ($defaults as $key => $values) {
            $config->set($key, $values);
        }

        // Then, load and merge overrides from project root config/
        $overrides = $this->loadConfigFiles($config->get('app.base_path').'/config');
        foreach ($overrides as $key => $values) {
            // Merge with existing config if it exists, otherwise just set
            if ($config->has($key)) {
                $existing = $config->get($key);
                if (is_array($existing) && is_array($values)) {
                    $merged = $this->mergeConfig($existing, $values);
                    $config->set($key, $merged);
                } else {
                    // Override completely if not both arrays
                    $config->set($key, $values);
                }
            } else {
                $config->set($key, $values);
            }
        }

        // Register config
        $container->set('config', $config);
        $container->set(ConfigInterface::class, $config);
    }

    /**
     * Load all config files from a directory.
     *
     * @param  string  $path  Directory path
     * @return array<string, array> Array of config arrays keyed by filename
     */
    protected function loadConfigFiles(string $path): array
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

    /**
     * Recursively merge two config arrays.
     * Override values take precedence.
     *
     * @param  array  $default  Default config array
     * @param  array  $override  Override config array
     * @return array Merged config array
     */
    protected function mergeConfig(array $default, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($default[$key]) && is_array($default[$key]) && is_array($value)) {
                // Recursively merge nested arrays
                $default[$key] = $this->mergeConfig($default[$key], $value);
            } else {
                // Override with new value
                $default[$key] = $value;
            }
        }

        return $default;
    }
}
