<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Soochak\EventManager;

/**
 * Event service provider.
 */
class EventProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register event manager
        $container->set('events', $container->share(function () {
            return new EventManager;
        }));

        $container->set(EventDispatcherInterface::class, fn (Container $c) => $c->get('events'));
        $container->set(EventManager::class, fn (Container $c) => $c->get('events'));
    }
}
