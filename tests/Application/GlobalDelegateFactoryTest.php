<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Application;

use Psr\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Factory\GlobalDelegateFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class GlobalDelegateFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDelegateBuildingChain()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('class')->willReturn(new Stubs\MiddlewareStub());
        $container->has(ResponseInterface::class)->willReturn(false);

        $container->get('middleware')->willReturn([
            'class'
        ]);

        $factory = new GlobalDelegateFactory();
        $this->assertInstanceOf(DelegateInterface::class, $factory->build($container->reveal()));
    }
}
