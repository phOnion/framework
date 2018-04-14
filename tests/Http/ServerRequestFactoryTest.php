<?php
namespace Tests\Factory;

use Psr\Container\ContainerInterface;
use Onion\Framework\Http\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @coversDefaultClass \Onion\Framework\Http\Factory\ServerRequestFactory
 */
class ServerRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryInit()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $factory = new ServerRequestFactory();

        $this->assertInstanceOf(ServerRequestInterface::class, $factory->build($container->reveal()));
    }
}
