<?php
/**
 * PHP Version 5.6.0
 *
 * @category Object-Factory
 * @package  Onion\Framework\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;

class GlobalMiddlewareFactory implements ObjectFactoryInterface
{
    public function __invoke(ContainerInterface $container)
    {
        /**
         * @var array[][] $middleware
         */
        $middleware = $container->get(Configuration::class)
            ->get('middleware');

        usort(
            $middleware,
            function ($object1, $object2) {
                if ($object1['priority'] > $object2['priority']) {
                    return 1;
                }

                if ($object1['priority'] < $object2['priority']) {
                    return -1;
                }

                return 0;
            }
        );

        /**
         * @var $stack StackInterface
         */
        $stack = $container->get(StackInterface::class);

        foreach ($middleware as $definitions) {
            array_walk(
                $definitions['stack'],
                function (&$value) use ($container) {
                    $value = $container->get($value);
                }
            );

            foreach ($definitions['stack'] as $middleware) {
                /**
                 * @var MiddlewareInterface $middleware
                 */
                $stack = $stack->withMiddleware($middleware);
            }
        }

        return $stack;
    }
}
