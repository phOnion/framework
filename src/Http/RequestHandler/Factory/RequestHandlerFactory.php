<?php

declare(strict_types=1);

namespace Onion\Framework\Http\RequestHandler\Factory;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

use function Onion\Framework\Common\generator;

class RequestHandlerFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        assert(
            $container->has('middleware'),
            new \RuntimeException(
                'Unable to initialize RequestHandler without defined middleware'
            )
        );
        $middlewareGenerator = function () use ($container): \Generator {
            $middleware = $container->get('middleware');
            foreach ($middleware as $identifier) {
                $instance = $container->get($identifier);
                assert(
                    $instance instanceof MiddlewareInterface,
                    new \TypeError("'{$identifier}' must implement MiddlewareInterface")
                );

                yield $instance;
            }
        };

        return new RequestHandler(
            generator($middlewareGenerator),
            $container->has(ResponseInterface::class) ?
                $container->get(ResponseInterface::class) : new Response()
        );
    }
}
