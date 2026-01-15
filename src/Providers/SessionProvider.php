<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Phew\ViewInterface;
use Phlash\ArrayFlash;
use Phlash\FlashInterface;

/**
 * Session service provider.
 */
class SessionProvider implements ProviderInterface
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

    public function init(Container $container): void
    {
        if ($container->has(ViewInterface::class)) {
            $view = $container->get(ViewInterface::class);
            $view->register('flash', function () use ($container) {
                return $container->get(FlashInterface::class);
            });
        }
    }
}
