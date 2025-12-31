<?php

declare(strict_types=1);

namespace Phast\Providers;

use Ank\CaptchaGenerator;
use Ank\CaptchaGeneratorInterface;
use Ank\MathCaptchaGenerator;
use Katora\Container;
use Katora\ServiceProviderInterface;

/**
 * CAPTCHA service provider.
 */
class CaptchaProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        // Register text-based CAPTCHA generator
        // Uses $_SESSION by default (handled by SessionProvider)
        $container->set('captcha.text', $container->share(function () {
            return new CaptchaGenerator;
        }));

        // Register math-based CAPTCHA generator
        // Uses $_SESSION by default (handled by SessionProvider)
        $container->set('captcha.math', $container->share(function () {
            return new MathCaptchaGenerator;
        }));

        // Register default CAPTCHA generator (text-based)
        $container->set('captcha', $container->share(function (Container $c) {
            return $c->get('captcha.text');
        }));

        // Register interface (defaults to text generator)
        $container->set(CaptchaGeneratorInterface::class, fn (Container $c) => $c->get('captcha'));
    }
}
