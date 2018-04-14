<?php declare(strict_types=1);

namespace Tests\Router;

use Psr\Http\Message\UriInterface;
use Onion\Framework\Router\RegexRoute;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegexRouteTest extends \PHPUnit_Framework_TestCase
{
    /** @var RegexRoute */
    private $route;
    public function setUp()
    {
        $this->route = new RegexRoute('/');
    }

    public function testRouteName()
    {
        $this->assertTrue($this->route->hasName());
        $this->assertSame('/', $this->route->getName());
    }

    public function testRouteGetPattern()
    {
        $this->assertSame('/', $this->route->getPattern());
    }

    public function testPatternHandling()
    {
        $route = new RegexRoute('/[test][/[bar]][/[num:\d+]]');
        $this->assertSame('/[test][/[bar]][/[num:\d+]]', $route->getName());
        $this->assertSame('/[test][/[bar]][/[num:\d+]]', $route->getPattern());
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
            'content-type' => 'text/plain',
        ];


        $response = $this->prophesize(ResponseInterface::class);
        $response->withAddedHeader('content-type', 'text/plain')
            ->willReturn($response->reveal());
        $response->withAddedHeader('Access-Control-Allow-Methods', 'GET')
            ->willReturn($response->reveal());
        $response->withAddedHeader('Access-Control-Max-Age', '86400')
            ->willReturn($response->reveal());
        $response->withAddedHeader('Access-Control-Allow-Origin', '*')
            ->willReturn($response->reveal());
        $response->withAddedHeader('Access-Control-Allow-Credentials', 'true')
            ->willReturn($response->reveal());
        $response->hasHeader('Access-Control-Allow-Origin')
            ->willReturn(false);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getAuthority()->willReturn('');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());

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

    public function testRouteMethods()
    {
        $route = $this->route->withMethods(new \ArrayIterator(['GET', 'HEAD']));
        $this->assertNotSame($this->route, $route);
        $this->assertTrue($route->hasMethod('GET'));
        $this->assertTrue($route->hasMethod('HEAD'));
        $this->assertFalse($route->hasMethod('POST'));
        $this->assertSame(['GET', 'HEAD'], $route->getMethods());
    }
}
