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
use Onion\Framework\Http\Middleware\Pipe;
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

        array_walk(
                $middleware,
                function (&$value) use ($container) {
                    $value = $container->get($value);
                }
            );

        return new Pipe($middleware);
    }
}
