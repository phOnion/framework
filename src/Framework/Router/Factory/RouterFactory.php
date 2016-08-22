<?php
/**
 * PHP Version 5.6.0
 *
 * @category Object-Factory
 * @package  Onion\Framework\Router\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces;
use Onion\Framework\Router\Router;

class RouterFactory implements Interfaces\ObjectFactoryInterface
{
    public function __invoke(ContainerInterface $container)
    {
        /**
         * @var $configuration ContainerInterface
         */
        $configuration = $container->get(Configuration::class);
        /**
         * @var $routes array
         */
        $routes = $configuration->get('routes');

        $router = new Router();
        $router->setParser(
            $container->get(Interfaces\Router\ParserInterface::class)
        )->setRouteRootObject(
            $container->get(Interfaces\Router\RouteInterface::class)
        );

        foreach ($routes as $route) {
            if (!array_key_exists('pattern', $route)
                || !array_key_exists('middleware', $route)
            ) {
                throw new \InvalidArgumentException(
                    'Every route definition must have "pattern" and "middleware" entry'
                );
            }

            $name = array_key_exists('name', $route) ?
                $route['name'] : null;

            $methods = array_key_exists('methods', $route) ?
                $route['methods'] : ['GET', 'HEAD'];

            array_walk(
                $route['middleware'],
                function (&$value) use ($container) {
                    if ($container->has($value)) {
                        $value = $container->get($value);
                        return;
                    }

                    throw new \InvalidArgumentException(
                        sprintf(
                            'Middleware "%s" is not registered in the container',
                            $value
                        )
                    );
                }
            );

            $router->addRoute(
                $methods,
                $route['pattern'],
                array_filter($route['middleware']),
                $name
            );
        }

        return $router;
    }
}
