<?php

namespace Tests\Router;

use Closure;
use Onion\Framework\Router\Route;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

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
        $route = (new Route('/'))->withMethods(['HEAD', 'GET']);
        $this->assertSame(['head', 'get'], $route->getMethods());
        $this->assertTrue($route->hasMethod('HEAD'));
        $this->assertFalse($route->hasMethod('PUT'));
        $this->assertNotSame($route, $route->withMethods(['HEAD', 'GET']));
    }

    public function testEmptyActionHandler()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler provided for route');
        (new Route('/'))->getAction();
    }

    public function testMiddlewareAsAction()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $route = (new Route('/'))->withAction($middleware->reveal());

        $this->assertInstanceOf(Closure::class, $route->getAction());
    }

    public function testParameters()
    {
        $route = (new Route('/'))->withParameters(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $route->getParameters());
        $this->assertSame('bar', $route->getParameter('foo'));
        $this->assertNull($route->getParameter('baz'));
        $this->assertNotSame($route, $route->withParameters(['foo' => 'bar']));
    }

    public function testRequestHandling()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('get');
        $request->withAttribute(new AnyValueToken, new AnyValueToken)->willReturn($request->reveal());

        $route = (new Route('/'))
            ->withMethods(['GET'])
            ->withAction(fn () => null);
        $route->getAction($request->reveal());
        $this->assertNotSame($route, $route->withAction(fn () => null));
    }
}
