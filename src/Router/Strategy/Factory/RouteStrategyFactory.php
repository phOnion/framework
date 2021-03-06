<?php
namespace Onion\Framework\Router\Strategy\Factory;

use function Onion\Framework\Common\generator;
use GuzzleHttp\Psr7\Response;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Onion\Framework\Router\Route;
use Onion\Framework\Router\Strategy\CompiledRegexStrategy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteStrategyFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        $target = $container->has('router.resolver') ?
            $container->get('router.resolver') : CompiledRegexStrategy::class;

        assert(class_exists($target), new \InvalidArgumentException(
            "Provided '{$target}' does not exist."
        ));

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

                $responseTemplate = $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response();

                /** @codeCoverageIgnore */
                $handler = new RequestHandler(generator(function () use ($route, $container) {
                    foreach ($route['middleware'] as $class) {
                        yield $container->get($class);
                    }
                }), $responseTemplate);

                yield $object->withRequestHandler($handler);
            }
        });

        return new $target(
            $generator,
            $container->has('router.count') ? $container->get('router.count') : null
        );
    }
}
