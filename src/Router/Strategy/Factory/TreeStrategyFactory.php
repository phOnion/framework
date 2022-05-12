<?php

declare(strict_types=1);

namespace Onion\Framework\Router\Strategy\Factory;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Onion\Framework\Router\Route;
use Onion\Framework\Router\Strategy\TreeStrategy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function Onion\Framework\generator;
use function Onion\Framework\merge;

class TreeStrategyFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        $target = $container->has('router.resolver') ?
            $container->get('router.resolver') : TreeStrategy::class;

        $groups = $container->has('router.groups') ?
            $container->get('router.groups') : [];

        foreach ($groups as $name => $group) {
            if (isset($group['extends'])) {
                assert(
                    isset($groups[$group['extends']]),
                    new RuntimeException(
                        "Route group '{$name}' attempts to extend group '{$group['extends']}' that does not exist"
                    )
                );
                $groups[$name] = merge($group, $groups[$group['extends']]);
            }
        }

        assert(class_exists($target), new \InvalidArgumentException(
            "Provided '{$target}' does not exist."
        ));

        $routes = $container->get('routes');
        $generator = generator(function () use ($groups, $routes, $container) {

            foreach ($routes as $route) {
                $group = isset($route['group']) ? merge([
                    'prefix' => '',
                    'middleware' => [],
                    'headers' => [],
                ], $groups[$route['group']] ?? []) : [
                    'prefix' => '',
                    'middleware' => [],
                    'headers' => [],
                ];

                assert(
                    isset($route['pattern']),
                    new \InvalidArgumentException("Missing 'pattern' key of route")
                );

                assert(
                    isset($route['middleware']),
                    new \InvalidArgumentException("Missing 'middleware' key of route")
                );

                /** @var Route $object */
                $object = (new Route("{$group['prefix']}{$route['pattern']}", $route['name'] ?? $route['pattern']))
                    ->withMethods($route['methods'] ?? ['GET', 'HEAD'])
                    ->withHeaders(array_merge($group['headers'], $route['headers'] ?? []));

                $responseTemplate = $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response();

                yield $object->withRequestHandler(new RequestHandler(generator(function () use ($group, $route, $container) {
                    foreach ([...$group['middleware'], ...$route['middleware']] as $class) {
                        yield $container->get($class);
                    }
                }), $responseTemplate));
            }
        });

        return new $target(
            $generator,
            $container->has('router.count') ? $container->get('router.count') : null
        );
    }
}
