<?php declare(strict_types=1);
namespace Onion\Framework\Router\Factory;

use Onion\Framework\Router\Route;
use Onion\Framework\Router\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Onion\Framework\Router\Matchers\Regex;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Interfaces\ParserInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\RequestHandler;

/**
 * Class RouterFactory
 *
 * @package Onion\Framework\Router\Factory
 */
final class RouterFactory implements FactoryInterface
{
    /** @var RouteInterface */
    private $route;

    /**
     * RouterFactory constructor.
     */
    public function __construct(RouteInterface $routeTemplate)
    {
        $this->route = $routeTemplate;
    }

    /**
     * @param ContainerInterface $container
     * @return Router
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function build(ContainerInterface $container): Router
    {
        assert($container->has('routes'), 'No routes defined in container');
        assert($container->has(ParserInterface::class), 'No global route parser defined in container');
        assert($container->has(MatcherInterface::class), 'No global route matcher defined in container');

        /**
         * @var $routes array[]
         */
        $routes = $container->get('routes');

        $router = new Router(
            $container->get(MatcherInterface::class)
        );
        /** @var ParserInterface $parser */
        $parser = $container->get(ParserInterface::class);

        foreach ($routes as $route) {
            assert(array_key_exists('pattern', $route), 'A route definition must have a "pattern" key');
            assert(array_key_exists('middleware', $route), 'A route definition must have a "middleware" key');
            $name = $route['name'] ?? null;
            $methods = array_intersect($route['methods'], RouterInterface::SUPPORTED_METHODS);

            array_walk($route['middleware'], function (&$value) use ($container) {
                $value = $container->get($value);
            });

            $router = $router->addRoute(
                $this->route->hydrate([
                    'pattern' => $parser->parse($route['pattern']),
                    'name' => $name,
                    'delegate' => new RequestHandler(
                        $route['middleware'],
                        $container->has(ResponseInterface::class) ?
                            $container->get(ResponseInterface::class) : null
                    ),
                    'methods' => $methods
                ])
            );
        }

        if ($container->has('modules')) {
            foreach ($container->get('modules') as $pattern => $module) {
                /** @var FactoryInterface $module */
                $router = $router->addRoute(
                    $this->route->hydrate([
                        'pattern' => $parser->parse($pattern),
                        'delegate' => $container->get($module)->build($container)
                    ])
                );
            }
        }

        return $router;
    }
}
