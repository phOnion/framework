<?php
namespace Tests\Application;

use Psr\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application;
use Onion\Framework\Application\Factory\ApplicationFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Zend\Diactoros\Response\EmitterInterface;

class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationContainerRetrieval()
    {
        $stack = $this->prophesize(DelegateInterface::class);
        $container = $this->prophesize(ContainerInterface::class);
        $emitter = $this->prophesize(EmitterInterface::class);
        $container->get(DelegateInterface::class)->willReturn($stack->reveal());
        $container->get(EmitterInterface::class)->willReturn($emitter->reveal());
        $container->has('modules')->willReturn(false);

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->assertInstanceOf(Application\Application::class, $factory->build($container->reveal()));
    }
}
