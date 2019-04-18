<?php
namespace Onion\Framework\Router\Strategy\Factory;

use function Onion\Framework\Common\generator;
use GuzzleHttp\Psr7\Response;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Onion\Framework\Router\Route;
use Onion\Framework\Router\Strategy\CompiledRegexStrategy;

class CompiledRegexStrategyFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
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

        return new CompiledRegexStrategy(
            $generator,
            $container->has('router.groupCount') ? $container->get('router.groupCount') : 10
        );
    }
}
