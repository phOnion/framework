<?php
/**
 * PHP Version 5.6.0
 *
 * @category Object-Factory
 * @package  Onion\Framework\Middleware\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;
use Onion\Framework\Interfaces\Router\RouterInterface;
use Onion\Framework\Middleware\RouteDispatchMiddleware;

class RouteDispatchMiddlewareFactory implements ObjectFactoryInterface
{
    public function __invoke(ContainerInterface $container)
    {
        return new RouteDispatchMiddleware(
            $container->get(RouterInterface::class),
            $container->get(StackInterface::class)
        );
    }
}
