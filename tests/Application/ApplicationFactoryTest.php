<?php
namespace Tests\Application;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Application;
use Onion\Framework\Application\Factory\ApplicationFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationContainerRetrieval()
    {
        $stack = $this->prophesize(RequestHandlerInterface::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(RequestHandlerInterface::class)->willReturn($stack->reveal());
        $container->has('modules')->willReturn(false);

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->assertInstanceOf(Application\Application::class, $factory->build($container->reveal()));
    }
}
