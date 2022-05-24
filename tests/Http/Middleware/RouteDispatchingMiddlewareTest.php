<?php

namespace Tests\Http\Middleware;

use Onion\Framework\Http\Middleware\RouteDispatchingMiddleware;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

    public function testProcessing()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());

        $request->withAttribute(RouteInterface::class, Argument::type(RouteInterface::class))
            ->willReturn($request->reveal())
            ->shouldBeCalledOnce();
        $request->withAttribute('route', Argument::type(RouteInterface::class))
            ->willReturn($request->reveal())
            ->shouldBeCalledOnce();

        $route = $this->prophesize(RouteInterface::class);
        $route->getAction()->willReturn(fn () => $this->prophesize(ResponseInterface::class)->reveal());

        $router = $this->prophesize(RouterInterface::class);
        $router->match($request->reveal())->willReturn($route->reveal());

        $middleware = new RouteDispatchingMiddleware($router->reveal());
        $this->isInstanceOf(ResponseInterface::class,  $middleware->process($request->reveal(), $this->prophesize(RequestHandlerInterface::class)->reveal()));
    }
}
