<?php

namespace Tests\Router;

use ArrayIterator;
use Onion\Framework\Router\Interfaces\CollectorInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Router;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RouterTest extends TestCase
{
    use ProphecyTrait;

    public function testFoo()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->hasMethod('GET')->willReturn(true)
            ->shouldBeCalledOnce();
        $route->withParameters([
            'bar' => '1337',
        ])->willReturn($route->reveal())
            ->shouldBeCalledOnce();

        $collector = $this->prophesize(CollectorInterface::class);
        $collector->getIterator()->willReturn(new ArrayIterator([
            '/foo/(?P<bar>\d+)(*MARK:1)' => ['1' => $route->reveal()],
        ]))->shouldBeCalledOnce();

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo/1337')
            ->shouldBeCalledOnce();
        $request = $this->prophesize(RequestInterface::class);
        $request->getMethod()->willReturn('GET')
            ->shouldBeCalledOnce();
        $request->getUri()->willReturn($uri->reveal())
            ->shouldBeCalledOnce();

        $router = new Router($collector->reveal());
        $this->assertInstanceOf(
            RouteInterface::class,
            $router->match($request->reveal())
        );
    }
}
