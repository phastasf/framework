<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Soochak\EventManager;
use Soochak\EventManagerInterface;

/**
 * Event service provider.
 */
class EventProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register event manager
        $container->set('events', $container->share(function () {
            return new EventManager;
        }));

        $container->set(EventDispatcherInterface::class, fn (Container $c) => $c->get('events'));
        $container->set(ListenerProviderInterface::class, fn (Container $c) => $c->get('events'));
        $container->set(EventManagerInterface::class, fn (Container $c) => $c->get('events'));
        $container->set(EventManager::class, fn (Container $c) => $c->get('events'));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
