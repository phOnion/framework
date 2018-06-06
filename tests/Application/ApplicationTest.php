<?php
namespace Tests;

use Psr\Http\Message\UriInterface;
use Prophecy\Argument\Token\TypeToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Onion\Framework\Application\Application;
use Psr\Http\Message\ServerRequestInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Log\VoidLogger;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $route;
    protected $request;

    public function setUp()
    {
        $this->route = $this->prophesize(RouteInterface::class);
        $this->route->getParameters()->willReturn([]);
        $this->route->hasMethod('GET')->willReturn(true);
        $this->route->getMethods()->willReturn(['GET']);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $uri->getHost()->willReturn('localhost');

        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->request->getUri()->willReturn($uri->reveal());
    }

    public function testApplicationRunNoRoute()
    {
        $this->request->getMethod()->willReturn('GET');

        $this->route->isMatch('/')->willReturn(false);
        $app = new Application([$this->route->reveal()]);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMethodNotAllowedException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new MethodNotAllowedException(['POST'])
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('allow'));
    }

    public function testMissingAuthorizationHeaderException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new MissingHeaderException('authorization')
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()], null, 'basic');
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('www-authenticate'));
        $this->assertSame(
            'Basic realm="localhost" charset="UTF-8"',
            $response->getHeaderLine('www-authenticate')
        );
    }

    public function testMissingProxyAuthorizationHeaderException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new MissingHeaderException('proxy-authorization')
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()], null, 'basic', 'bearer');
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(407, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('proxy-authenticate'));
        $this->assertSame(
            'Bearer realm="localhost" charset="UTF-8"',
            $response->getHeaderLine('proxy-authenticate')
        );
    }

    public function testConditionHeaderException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new MissingHeaderException('if-modified-since')
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testGenericHeaderException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new MissingHeaderException('x-custom-header')
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testBadMethodCallException()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->route->handle(new AnyValueToken())->willThrow(
            new \BadMethodCallException('Foo')
        );

        $this->route->isMatch('/')->willReturn(true);
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testApplicationRun()
    {
        $this->request->getMethod()->willReturn('GET');

        $this->route->isMatch('/')->willReturn(true);
        $this->route->handle(new AnyValueToken())->willThrow(new \ErrorException('OK'));
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);
        $response = $app->handle($this->request->reveal());

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testRouteParameters()
    {
        $this->request->withAttribute('test', 'test')->shouldBeCalled();
        $this->route->isMatch('/')->willReturn(true);
        $this->route->getParameters()->willReturn(['test' => 'test']);
        $app = new Application([$this->route->reveal()]);
        $app->setLogger(new VoidLogger);

        $app->handle($this->request->reveal());
    }
}
