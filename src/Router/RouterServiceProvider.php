<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use Onion\Framework\Router\Interfaces\CollectorInterface;
use Onion\Framework\Router\Interfaces\ParserInterface;

class RouterServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $provider): void
    {
        $provider->singleton(ParserInterface::class, static fn () => new Parser());
        $provider->singleton(CollectorInterface::class, static fn ($c) => new Collector(
            $c->get(ParserInterface::class)
        ));

        $provider->bind(Router::class, fn ($c) => new Router(
            $c->get(CollectorInterface::class)
        ));
    }
}
