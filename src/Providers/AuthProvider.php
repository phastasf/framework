<?php

declare(strict_types=1);

namespace Phast\Providers;

use Jweety\Encoder;
use Jweety\EncoderInterface;
use Katora\Container;

/**
 * Authentication service provider.
 */
class AuthProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register JWT encoder
        $container->set('auth.encoder', $container->share(function (Container $c) {
            $config = $c->get('config');
            $secret = $config->get('auth.jwt.secret', 'your-secret-key-change-this-in-production');
            $algorithm = $config->get('auth.jwt.algorithm', 'HS256');

            return new Encoder($secret, $algorithm);
        }));

        $container->set(EncoderInterface::class, fn (Container $c) => $c->get('auth.encoder'));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
