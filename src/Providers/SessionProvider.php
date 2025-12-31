<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;
use Phlash\ArrayFlash;
use Phlash\FlashInterface;

/**
 * Session service provider.
 */
class SessionProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register flash messages
        $container->set('flash', $container->share(function () {
            return new ArrayFlash;
        }));

        // Register interface
        $container->set(FlashInterface::class, fn (Container $c) => $c->get('flash'));
        $container->set(ArrayFlash::class, fn (Container $c) => $c->get('flash'));
    }
}
