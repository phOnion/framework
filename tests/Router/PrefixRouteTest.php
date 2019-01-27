<?php declare(strict_types=1);
namespace Tests\Router;

use Onion\Framework\Router\PrefixRoute;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PrefixRouteTest extends \PHPUnit\Framework\TestCase
{
    private $route;
    public function setUp()
    {
        $this->route = new PrefixRoute('/foo');
    }

    public function testMatch()
    {
        $this->assertSame($this->route->getName(), '/foo/*');
        $this->assertSame($this->route->getPattern(), '/foo/*');
        $this->assertTrue($this->route->isMatch('/foo/test/some'));
    }

    public function testMatchHandling()
    {
        $response = $this->prophesize(ResponseInterface::class);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo/bar');
        $uri->withPath('/bar')->willReturn($uri->reveal())->shouldBeCalled();
        $uri->withPath('/bar')->shouldBeCalled();
        $uri->withPath('/foo/bar')->willReturn($uri->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->withUri(new AnyValueToken())->willReturn($request->reveal());
        $request->getMethod()->willReturn('get');
        $request->getUri()->willReturn($uri->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(new AnyValueToken())->willReturn($response->reveal());

        $route = $this->route->withRequestHandler($handler->reveal())
            ->withMethods(['GET']);
        $this->assertNotSame($this->route, $route);

        $route->handle($request->reveal());
    }
}
