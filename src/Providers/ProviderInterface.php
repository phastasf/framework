<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Katora\ServiceProviderInterface;

/**
 * Extended service provider interface with initialization support.
 */
interface ProviderInterface extends ServiceProviderInterface
{
    /**
     * Initialize the provider after all providers have been registered.
     *
     * This method is called after all service providers have finished
     * registering their services, allowing for initialization logic that
     * depends on other services being available.
     *
     * @param  Container  $container  The container instance
     */
    public function init(Container $container): void;
}
