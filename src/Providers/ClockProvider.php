<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;
use Psr\Clock\ClockInterface;
use Samay\SystemClock;

/**
 * Clock service provider.
 */
class ClockProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register clock
        $container->set('clock', $container->share(function () {
            return new SystemClock;
        }));

        $container->set(ClockInterface::class, fn (Container $c) => $c->get('clock'));
    }
}
