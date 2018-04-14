<?php declare(strict_types=1);
namespace Tests\Http;

use Test\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Factory\RequestHandlerFactory;

class RequestHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var FactoryInterface */
    private $factory;
    public function setUp()
    {
        $this->factory = new RequestHandlerFactory();
    }

    public function testProperInit()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('middleware')->willReturn(true);
        $container->get('middleware')->willReturn(['foo']);
        $container->get('foo')->willReturn(
            $this->prophesize(MiddlewareInterface::class)->reveal()
        );

        $this->assertInstanceOf(RequestHandlerInterface::class, $this->factory->build(
            $container->reveal()
        ));
    }

    public function testInvalidMiddlewareReturned()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('middleware')->willReturn(true);
        $container->get('middleware')->willReturn(['foo']);
        $container->get('foo')->willReturn('blah');

        $this->assertInstanceOf(RequestHandlerInterface::class, $this->factory->build(
            $container->reveal()
        ));
    }

    public function testExceptionOnMissingMiddlewareKey()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('middleware')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->factory->build($container->reveal());
    }
}
