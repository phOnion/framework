<?php
namespace Onion\Framework\Router\Strategy\Factory;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Onion\Framework\Router\Route;
use Onion\Framework\Router\Strategy\CompiledRegexStrategy;
use Psr\Container\ContainerInterface;
use function Onion\Framework\Common\generator;

class RouteStrategyFactory implements FactoryBuilderInterface
{
    public function build(ContainerInterface $container, string $key): FactoryInterface
    {
        $key = $container->has('router.resolver') ? $container->get('router.resolver') : CompiledRegexStrategy::class;
        return new class($key) implements FactoryInterface {
            private $target;

            public function __construct(string $target)
            {
                $this->target = $target;
            }

            public function build(ContainerInterface $container)
            {
                $routes = $container->get('routes');
                $generator = generator(function () use ($routes, $container) {
                    foreach ($routes as $route) {
                        assert(
                            isset($route['pattern']),
                            new \InvalidArgumentException("Missing 'pattern' key of route")
                        );

                        assert(
                            isset($route['middleware']),
                            new \InvalidArgumentException("Missing 'middleware' key of route")
                        );

                        /** @var Route $object */
                        $object = (new Route($route['pattern'], $route['name'] ?? $route['pattern']))
                            ->withMethods($route['methods'] ?? ['GET', 'HEAD'])
                            ->withHeaders($route['headers'] ?? []);

                        $middleware = function () use ($route, $container) {
                            foreach ($route['middleware'] as $class) {
                                yield $container->get($class);
                            }
                        };

                        $handler = new RequestHandler($middleware(), new Response());

                        yield $object->withRequestHandler($handler);
                    }
                });

                $target = $this->target;

                return new $target($generator);
            }
        };
    }
}
