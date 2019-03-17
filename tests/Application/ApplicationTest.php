<?php
namespace Tests;

use Onion\Framework\Application\Application;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;

class ApplicationTest extends \PHPUnit\Framework\TestCase
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

    public function testInvokeOfRequestHandler()
    {
        $requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $requestHandler->handle(new \Prophecy\Argument\Token\TypeToken(ServerRequestInterface::class))
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal())
            ->shouldBeCalled(1);

        (new Application($requestHandler->reveal()))->run($this->request->reveal());
    }
}
