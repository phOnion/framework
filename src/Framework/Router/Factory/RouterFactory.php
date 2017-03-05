<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Factory;

use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Interfaces\ParserInterface;
use Onion\Framework\Router\Matchers\Regex;
use Onion\Framework\Router\Router;
use Psr\Http\Message\ResponseInterface;

final class RouterFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        assert($container->has('routes'), 'No routes defined in container');
        assert($container->has(ParserInterface::class), 'No global route parser defined in container');
        assert($container->has(MatcherInterface::class), 'No global route matcher defined in container');

        /**
         * @var $routes array[]
         */
        $routes = $container->get('routes');

        $router = new Router(
            $container->get(ParserInterface::class),
            $container->get(MatcherInterface::class)
        );

        foreach ($routes as $route) {
            assert(array_key_exists('pattern', $route), 'A route definition must have a "pattern" key');
            assert(array_key_exists('middleware', $route), 'A route definition must have a "middleware" key');

            $name = $route['name'] ?? null;
            $methods = $route['methods'] ?? ['GET', 'HEAD'];

            $stack = [];
            foreach ($route['middleware'] as $middleware) {
                $stack[] = $container->get($middleware);
            }

            $router->addRoute(
                $route['pattern'],
                new Delegate(
                    $stack,
                    $container->has(ResponseInterface::class) ?
                        $container->get(ResponseInterface::class) : null
                ),
                $methods,
                $name
            );
        }

        return $router;
    }
}
