<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Strategy\Factory\TreeStrategyFactory;

class RouterServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $provider): void
    {
        $provider->bind(ResolverInterface::class, TreeStrategyFactory::class);
    }
}
