<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Kunfig\ConfigInterface;
use Phew\View;
use Phew\ViewInterface;

/**
 * View service provider.
 */
class ViewProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register view paths service (can be overridden)
        $container->set('view.paths', $container->share(function (Container $c) {
            $config = $c->get('config');
            $paths = $config->get('view.paths', []);

            // Convert ConfigInterface to array if needed
            if ($paths instanceof ConfigInterface) {
                $paths = $paths->all();
            }

            $result = [];

            // Process paths from config
            if (is_array($paths) && ! empty($paths)) {
                foreach ($paths as $pathConfig) {
                    if (is_string($pathConfig)) {
                        // Simple string path (default namespace)
                        $path = $pathConfig;
                        $namespace = '';
                    } elseif (is_array($pathConfig) && isset($pathConfig['path'])) {
                        // Array with path and optional namespace
                        $path = $pathConfig['path'];
                        $namespace = $pathConfig['namespace'] ?? '';
                    } else {
                        continue; // Skip invalid entries
                    }

                    // Handle relative paths
                    if (! str_starts_with($path, '/') && $path !== ':memory:') {
                        $basePath = $config->get('app.base_path');
                        $path = $basePath.'/'.ltrim($path, '/');
                    }

                    $result[] = ['path' => $path, 'namespace' => $namespace];
                }
            }

            // Add default package views path (at bottom, lowest priority)
            $defaultPath = __DIR__.'/../../resources/views';
            if (is_dir($defaultPath)) {
                $result[] = ['path' => $defaultPath, 'namespace' => ''];
            }

            return $result;
        }));

        // Register view engine
        $container->set('view', $container->share(function (Container $c) {
            $view = new View;

            // Get paths from container service
            $paths = $c->get('view.paths');

            // Add each path to the view
            foreach ($paths as $pathConfig) {
                $view->add($pathConfig['path'], $pathConfig['namespace']);
            }

            return $view;
        }));

        $container->set(ViewInterface::class, fn (Container $c) => $c->get('view'));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
