<?php declare(strict_types=1);
namespace Tests\Http;

use Test\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Factory\RequestHandlerFactory;

class RequestHandlerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FactoryInterface */
    private $factory;
    public function setUp()
    {
        $this->factory = new RequestHandlerFactory();
    }

    public function testProperInit()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware->process(new AnyValueToken(), new AnyValueToken())
            ->willReturn($this->prophesize(
                ResponseInterface::class
            ));

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(RequestHandlerInterface::class)->willReturn(false);
        $container->has(ResponseInterface::class)->willReturn(true);
        $container->get(ResponseInterface::class)->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );
        $container->has('middleware')->willReturn(true);
        $container->get('middleware')->willReturn(['foo']);
        $container->get('foo')->willReturn($middleware->reveal());

        $handler = $this->factory->build($container->reveal());
        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
        $this->assertInstanceOf(
            ResponseInterface::class,
            $handler->handle($this->prophesize(ServerRequestInterface::class)->reveal())
        );
    }

    public function testInvalidMiddlewareReturned()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(RequestHandlerInterface::class)->willReturn(false);
        $container->has(ResponseInterface::class)->willReturn(false);
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
        $container->has(RequestHandlerInterface::class)->willReturn(false);
        $container->has('middleware')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->factory->build($container->reveal());
    }
}
