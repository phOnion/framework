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

        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->request->getUri()->willReturn($uri->reveal());
    }

    public function testApplicationRunNoRoute()
    {
        $this->expectException(NotFoundException::class);
        $this->request->getMethod()->willReturn('GET');

        $this->route->isMatch('/')->willReturn(false);
        $app = new Application([$this->route->reveal()]);
        $app->run($this->request->reveal());
    }

    public function testApplicationRunBadMethod()
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->request->getMethod()->willReturn('HEAD');
        $this->route->hasMethod('HEAD')->willReturn(false);
        $this->route->isMatch('/')->willReturn(true);

        $app = new Application([$this->route->reveal()]);
        $app->run($this->request->reveal());
    }

    public function testApplicationRun()
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('OK');
        $this->request->getMethod()->willReturn('GET');

        $this->route->isMatch('/')->willReturn(true);
        $this->route->handle(new AnyValueToken())->willThrow(new \ErrorException('OK'));
        $app = new Application([$this->route->reveal()]);
        $app->run($this->request->reveal());
    }
}
