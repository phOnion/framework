<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Application;
use Onion\Framework\Factory\ApplicationFactory;
use Onion\Framework\Http\Middleware\Stack;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;

class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationContainerRetrieval()
    {
        $stack = $this->prophesize(StackInterface::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Stack::class)->willReturn($stack->reveal());
        $container->get(StackInterface::class)->willReturn($stack->reveal());

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(ObjectFactoryInterface::class, $factory);
        $this->assertInstanceOf(Application::class, $factory($container->reveal()));
    }
}
