<?php

declare(strict_types=1);

namespace Phast\Providers;

use Dakiya\Client;
use Katora\Container;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * HTTP client service provider.
 */
class HttpClientProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register HTTP client (Dakiya - PSR-18 compliant) bound to ClientInterface
        $container->set('http.client', $container->share(function (Container $c) {
            return new Client($c->get(ResponseFactoryInterface::class));
        }));

        $container->set(ClientInterface::class, fn (Container $c) => $c->get('http.client'));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
