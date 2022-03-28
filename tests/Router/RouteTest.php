<?php

namespace Tests\Router;

use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Route;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteTest extends TestCase
{
    use ProphecyTrait;

    public function testNaming()
    {
        $route = new Route('/', 'home');
        $this->assertSame('home', $route->getName());
        $this->assertTrue($route->hasName());
        $route = new Route('/');
        $this->assertSame('/', $route->getName());
        $this->assertFalse($route->hasName());
    }

    public function testPattern()
    {
        $route = new Route('/');
        $this->assertSame('/', $route->getPattern());
    }

    public function testMethods()
    {
        $route = (new Route('/'))->withMethods(['head', 'get']);
        $this->assertSame(['head', 'get'], $route->getMethods());
        $this->assertTrue($route->hasMethod('HEAD'));
        $this->assertFalse($route->hasMethod('PUT'));
        $this->assertNotSame($route, $route->withMethods(['head', 'get']));
    }

    public function testEmptyRequestHandler()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler provided for route');
        (new Route('/'))->getRequestHandler();
    }

    public function testParameters()
    {
        $route = (new Route('/'))->withParameters(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $route->getParameters());
        $this->assertSame('bar', $route->getParameter('foo'));
        $this->assertNull($route->getParameter('baz'));
        $this->assertNotSame($route, $route->withParameters(['foo' => 'bar']));
    }

    public function testHeaders()
    {
        $route = (new Route('/'))->withHeaders(['accept' => 'application/json']);
        $this->assertSame(['accept' => 'application/json'], $route->getHeaders());
        $this->assertNotSame($route, $route->withHeaders(['accept' => 'application/json']));
    }

    public function testRequestHandling()
    {
        $requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $requestHandler->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('get');
        $request->withAttribute(new AnyValueToken, new AnyValueToken)->willReturn($request->reveal());

        $handler = $requestHandler->reveal();
        $route = (new Route('/'))
            ->withMethods(['GET'])
            ->withRequestHandler($handler);
        $route->handle($request->reveal());
        $this->assertNotSame($route, $route->withRequestHandler($handler));
    }

    public function testRequestHandlingWithMissingRequiredHeader()
    {
        $requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $requestHandler->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('get');
        $request->hasHeader('x-foo')->willReturn(false);
        $request->hasHeader('x-test')->willReturn(false);

        $this->expectException(MissingHeaderException::class);
        $this->expectExceptionMessage("Missing header 'x-test'");

        $route = (new Route('/'))
            ->withRequestHandler($requestHandler->reveal())
            ->withHeaders([
                'X-FOO' => false,
                'X-TEST' => true,
            ]);
        $route->handle($request->reveal());
    }

    public function testRequestHandlingWithUnsupportedMethod()
    {
        $requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $requestHandler->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('get');

        $this->expectException(MethodNotAllowedException::class);

        $route = (new Route('/'))
            ->withRequestHandler($requestHandler->reveal())
            ->withMethods(['PUT', 'PATCH']);
        $route->handle($request->reveal());
    }
}
