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
            // First, load default configs from package root config/
            $defaults = $this->loadConfigFiles(__DIR__.'/../../config');
            $config = new Config($defaults);

            // Then, load and merge overrides from project root config/
            $overrides = $this->loadConfigFiles($config->get('app.base_path').'/config');
            $config->mix(new Config($overrides));

            return $config;
        }));
        $container->set(ConfigInterface::class, fn () => $container->get('config'));
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
}
