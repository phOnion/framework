<?php
namespace Tests;

use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;
use Onion\Framework\Application\Application;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $stack;
    public function setUp()
    {
        $this->stack = $this->prophesize(RequestHandlerInterface::class);
    }

    public function testApplicationInvocation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OK');

        $this->stack->handle(
            new TypeToken(ServerRequestInterface::class)
        )->willThrow(new \RuntimeException('OK'));
        $app = new Application($this->stack->reveal());

        $app->run($this->prophesize(ServerRequestInterface::class)->reveal());
    }

    public function testApplicationRunWithoutNextFrame()
    {
        $this->stack->handle(
            new TypeToken(RequestInterface::class),
            null
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal()
        );

        $this->assertInstanceOf(
            ResponseInterface::class,
            $app->handle($this->prophesize(ServerRequestInterface::class)->reveal(), null)
        );
    }

    public function testExceptionRethrowWhenNoNextDelegateIsAvailable()
    {
        $this->stack->handle(new TypeToken(RequestInterface::class), null)
            ->willThrow(\Exception::class);

        $app = new Application(
            $this->stack->reveal()
        );

        $this->expectException(\Throwable::class);
        $app->handle($this->prophesize(ServerRequestInterface::class)->reveal(), null);
    }

    public function testApplicationFrameRun()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->willImplement(ServerRequestInterface::class);
        $this->stack->handle(
            new TypeToken(ServerRequestInterface::class),
            null
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal()
        );

        $middleware = $this->prophesize(RequestHandlerInterface::class);
        $middleware->handle(new TypeToken(ServerRequestInterface::class))->willReturn(null);

        $this->assertInstanceOf(ResponseInterface::class, $app->handle($request->reveal(), $middleware->reveal()));
    }
}
