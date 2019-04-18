<?php
namespace Tests\Http\Middleware;

use Onion\Framework\Http\Middleware\HttpErrorMiddleware;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpErrorMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    private $handler;
    private $middleware;
    private $request;

    public function setUp()
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new HttpErrorMiddleware();
        $request = $this->prophesize(ServerRequestInterface::class);
        $uri = $this->prophesize(UriInterface::class);
        $uri->getHost()->willReturn('example.com');
        $request->getUri()->willReturn($uri->reveal());
        $this->request = $request;
    }

    public function testAuthorizationException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('authorization'));

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('www-authenticate'));
    }

    public function testProxyAuthorizationException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('proxy-authorization'));

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(407, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('proxy-authenticate'));
    }

    public function testConditionalsException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-match'));

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testCustomHeaderException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('x-custom'));

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNotFoundException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new NotFoundException());

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUnsupportedMethodException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MethodNotAllowedException(['get', 'head']));

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame('get, head', $response->getHeaderLine('allow'));
    }

    public function testNotImplementedException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \BadMethodCallException());
        $this->request->getMethod()->willReturn('get');

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(503, $response->getStatusCode());

        $this->request->getMethod()->willReturn('post');

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(501, $response->getStatusCode());
    }

    public function testUnknownErrorException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \Exception());

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(500, $response->getStatusCode());
    }
}
