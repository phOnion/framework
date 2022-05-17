<?php

declare(strict_types=1);

namespace Onion\Framework\Http;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Stream\StreamInterface;
use Onion\Framework\Dependency\Interfaces\{ContainerInterface, ServiceProviderInterface};
use Onion\Framework\Http\Emitter\PhpEmitter;
use Onion\Framework\Http\Emitter\StreamEmitter;
use Onion\Framework\Http\Middleware\{
    HttpErrorMiddleware,
    RouteDispatchingMiddleware
};
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class HttpServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $provider): void
    {
        $provider->singleton(
            HttpErrorMiddleware::class,
            static function (ContainerInterface $c) {
                return new HttpErrorMiddleware(
                    logger: $c->has(LoggerInterface::class) ?
                        $c->get(LoggerInterface::class) : null,
                );
            }
        );
        $provider->singleton(
            RouteDispatchingMiddleware::class,
            static fn (ContainerInterface $c) => new RouteDispatchingMiddleware(
                $c->get(ResolverInterface::class)
            ),
        );
        $provider->bind(
            StreamEmitter::class,
            static fn ($c) => new StreamEmitter($c->get(StreamInterface::class))
        );
        $provider->singleton(PhpEmitter::class, PhpEmitter::class);
        $provider->singleton(ResponseInterface::class, Response::class);
        $provider->bind(
            RequestHandlerInterface::class,
            static fn (ContainerInterface $container) => new RequestHandler(
                $container->tagged('middleware'),
                $container->get(ResponseInterface::class)
            )
        );
    }
}
