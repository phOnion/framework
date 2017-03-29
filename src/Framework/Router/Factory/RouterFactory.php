<?php declare(strict_types=1);
namespace Onion\Framework\Router\Factory;

use Onion\Framework\Router\Route;
use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Interfaces\ParserInterface;
use Onion\Framework\Router\Matchers\Regex;
use Onion\Framework\Router\Router;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RouterFactory
 *
 * @package Onion\Framework\Router\Factory
 */
final class RouterFactory implements FactoryInterface
{
    private $route;

    /**
     * RouterFactory constructor.
     */
    public function __construct()
    {
        $this->route = new Route();
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

        foreach ($routes as $route) {
            assert(array_key_exists('pattern', $route), 'A route definition must have a "pattern" key');
            assert(array_key_exists('middleware', $route), 'A route definition must have a "middleware" key');
            $name = $route['name'] ?? null;
            $methods = ['GET', 'HEAD'];

            array_walk($route['middleware'], function (&$value) use ($container) {
                $value = $container->get($value);
            });

            $router->addRoute(
                $this->route->hydrate([
                    'pattern' => $container->get(ParserInterface::class)->parse($route['pattern']),
                    'name' => $name,
                    'delegate' => new Delegate(
                        $route['middleware'],
                        $container->has(ResponseInterface::class) ?
                            $container->get(ResponseInterface::class) : null
                    ),
                    'methods' => $methods
                ])
            );
        }

        return $router;
    }
}
