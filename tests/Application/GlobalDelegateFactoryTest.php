<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Application;

use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Application\Factory\GlobalDelegateFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class GlobalDelegateFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testObjectConstruction()
    {
        /**
         * @var $container Container
         */
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('middleware')->willReturn([]);

        $factory = new GlobalDelegateFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->expectException(\TypeError::class);
        $this->assertInstanceOf(DelegateInterface::class, $factory->build($container->reveal()));
    }

    public function testDelegateBuildingChain()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('class')->willReturn(new Stubs\MiddlewareStub());

        $container->get('middleware')->willReturn([
            'class'
        ]);

        $factory = new GlobalDelegateFactory();
        $this->assertInstanceOf(DelegateInterface::class, $factory->build($container->reveal()));
    }
}
