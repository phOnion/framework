<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware\Factory;

use Onion\Framework\Router\Route;
use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\RequestHandler;

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

        $middlewareGenerator = function () use ($container) {
            $middleware = $container->get('middleware');

            foreach ($middleware as $identifier) {
                $instance = $container->get($identifier);
                assert(
                    is_object($instance) && $instance instanceof MiddlewareInterface,
                    new \TypeError("'{$identifier}' must implement MiddlewareInterface")
                );

                yield $instance;
            }
        };
        return new RequestHandler($middlewareGenerator());
    }
}
