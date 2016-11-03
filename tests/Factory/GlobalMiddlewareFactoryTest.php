<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Factory;

use Onion\Framework\Configuration;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Factory\GlobalMiddlewareFactory;
use Onion\Framework\Interfaces\ObjectFactoryInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;

class GlobalMiddlewareFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testObjectConstruction()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('middleware')->willReturn([
        ]);
        $stack = $this->prophesize(StackInterface::class);
        /**
         * @var $container Container
         */
        $container = $this->prophesize(Container::class);
        $container->get(Configuration::class)
            ->willReturn($config->reveal());
        $container->get(StackInterface::class)
            ->willReturn($stack->reveal());

        $factory = new GlobalMiddlewareFactory();
        $this->assertInstanceOf(ObjectFactoryInterface::class, $factory);
        $this->assertInstanceOf(StackInterface::class, $factory($container->reveal()));
    }
}
