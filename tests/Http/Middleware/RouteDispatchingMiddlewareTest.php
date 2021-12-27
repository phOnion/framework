<?php

namespace Tests\Http\Middleware;

use Onion\Framework\Http\Middleware\RouteDispatchingMiddleware;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteDispatchingMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    public function testProcessingWithHeaders()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getHeaders()->willReturn(['foo' => 'bar', 'bar' => 'baz']);
        $response->getStatusCode()->willReturn(200);
        $response->getProtocolVersion()->willReturn('1.1');
        $response->getBody()->willReturn($this->prophesize(StreamInterface::class)->reveal());
        $response->hasHeader('foo')->willReturn(false);
        $response->hasHeader('bar')->willReturn(true);

        $response->withStatus(200)
            ->willReturn($response->reveal())
            ->shouldBeCalledOnce();
        $response->withBody(new TypeToken(StreamInterface::class))
            ->willReturn($response->reveal())
            ->shouldBeCalledOnce();
        $response->withProtocolVersion('1.1')
            ->willReturn($response->reveal())
            ->shouldBeCalledOnce();
        $response->withHeader('foo', 'bar')
            ->willReturn($response->reveal())
            ->shouldBeCalledOnce();
        $response->withAddedHeader('bar', 'baz')
            ->willReturn($response->reveal())
            ->shouldBeCalledOnce();

        $route = $this->prophesize(RouteInterface::class);
        $route->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($response->reveal());

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('GET');
        $request->getUri()->willReturn($uri->reveal());

        $request->withAttribute('route', new TypeToken(RouteInterface::class))
            ->willReturn($request->reveal())
            ->shouldBeCalledOnce();

        $resolver = $this->prophesize(ResolverInterface::class);
        $resolver->resolve('GET', '/')
            ->willReturn($route->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($response->reveal());

        $middleware = new RouteDispatchingMiddleware(
            $resolver->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $middleware->process(
            $request->reveal(),
            $handler->reveal()
        ));
    }
}
