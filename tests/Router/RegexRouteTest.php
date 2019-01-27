<?php declare(strict_types=1);

namespace Tests\Router;

use Psr\Http\Message\UriInterface;
use Onion\Framework\Router\RegexRoute;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;

class RegexRouteTest extends \PHPUnit\Framework\TestCase
{
    /** @var RegexRoute */
    private $route;
    public function setUp()
    {
        $this->route = new RegexRoute('/');
    }

    public function testRouteName()
    {
        $this->assertFalse($this->route->hasName());
        $this->assertSame('/', $this->route->getName());
    }

    public function testRouteGetPattern()
    {
        $this->assertSame('/', $this->route->getPattern());
    }

    public function testPatternHandling()
    {
        $route = new RegexRoute('/{test}{/{bar}{/{num:\d+}}?}?');
        $this->assertSame('/{test}{/{bar}{/{num:\d+}}?}?', $route->getName());
        $this->assertSame('/{test}{/{bar}{/{num:\d+}}?}?', $route->getPattern());
        $this->assertTrue($route->isMatch('/foo'));
        $this->assertSame(['test' => 'foo'], $route->getParameters());
        $this->assertTrue($route->isMatch('/foo/baz'));
        $this->assertSame(['test' => 'foo', 'bar' => 'baz'], $route->getParameters());
        $this->assertFalse($route->isMatch('/foo/baz/abc'));
        $this->assertTrue($route->isMatch('/foo/baz/123'));
        $this->assertSame([
            'test' => 'foo',
            'bar' => 'baz',
            'num' => '123'
        ], $route->getParameters());
    }

    public function testRouteRequestHandler()
    {
        $headers = [
            'content-type' => true,
        ];


        $response = $this->prophesize(ResponseInterface::class);

        $uri = $this->prophesize(UriInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('content-type')->willReturn(true);
        $request->getUri()->willReturn($uri->reveal());
        $request->getMethod()->willReturn('get');

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(new AnyValueToken())->willReturn($response->reveal());

        $route = $this->route->withRequestHandler($handler->reveal())
            ->withHeaders(new \ArrayIterator($headers))
            ->withMethods(['GET']);
        $this->assertNotSame($this->route, $route);

        $this->assertInstanceOf(
            ResponseInterface::class,
            $route->handle($request->reveal())
        );
    }

    public function testExceptionWhenMissingRequiredHeader()
    {
        $headers = [
            'content-type' => true,
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $uri = $this->prophesize(UriInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('content-type')->willReturn(false);
        $request->getUri()->willReturn($uri->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(new AnyValueToken())->willReturn($response->reveal());

        $route = $this->route->withRequestHandler($handler->reveal())
            ->withHeaders(new \ArrayIterator($headers))
            ->withMethods(['GET']);
        $this->assertNotSame($this->route, $route);

        $this->expectException(MissingHeaderException::class);
        $route->handle($request->reveal());
    }

    public function testExceptionWhenMethodNotSupported()
    {
        $uri = $this->prophesize(UriInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->hasHeader('content-type')->willReturn(false);
        $request->getUri()->willReturn($uri->reveal());

        $route = $this->route->withMethods(['GET']);
        $this->assertNotSame($this->route, $route);

        $this->expectException(MethodNotAllowedException::class);
        $route->handle($request->reveal());
    }

    public function testRouteMethods()
    {
        $route = $this->route->withMethods(new \ArrayIterator(['GET', 'HEAD']));
        $this->assertNotSame($this->route, $route);
        $this->assertTrue($route->hasMethod('GET'));
        $this->assertTrue($route->hasMethod('HEAD'));
        $this->assertFalse($route->hasMethod('POST'));
        $this->assertSame(['get', 'head'], $route->getMethods());
    }
}
