<?php declare(strict_types=1);
namespace Tests\Http;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\RequestHandler\Factory\RequestHandlerFactory;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $m2 = clone $middleware;

        $response = $this->prophesize(
            ResponseInterface::class
        );
        $response->getStatusCode()->willReturn(200);

        $middleware->process(new AnyValueToken(), new AnyValueToken())
            ->willReturn($response->reveal());

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
        $r = $handler->handle($this->prophesize(ServerRequestInterface::class)->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $r);
        $this->assertSame(200, $r->getStatusCode());
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
