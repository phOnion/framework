<?php
namespace Tests;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Application;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $emitter;
    protected $stack;
    public function setUp()
    {
        $this->emitter = $this->prophesize(EmitterInterface::class);
        $this->stack = $this->prophesize(DelegateInterface::class);
    }

    public function testApplicationInvocation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OK');

        $this->emitter->emit(new AnyValueToken())->willThrow(new \RuntimeException('OK'));
        $this->stack->process(
            new TypeToken(ServerRequestInterface::class)
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $app->run($this->prophesize(ServerRequestInterface::class)->reveal());
    }

    public function testApplicationRunWithoutNextFrame()
    {
        $this->stack->process(
            new TypeToken(RequestInterface::class),
            null
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $this->assertInstanceOf(
            ResponseInterface::class,
            $app->process($this->prophesize(ServerRequestInterface::class)->reveal(), null)
        );
    }

    public function testExceptionRethrowWhenNoNextDelegateIsAvailable()
    {
        $this->stack->process(new TypeToken(RequestInterface::class), null)
            ->willThrow(\Exception::class);

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $this->expectException(\Throwable::class);
        $app->process($this->prophesize(ServerRequestInterface::class)->reveal(), null);
    }

    public function testApplicationFrameRun()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->willImplement(ServerRequestInterface::class);
        $this->stack->process(
            new TypeToken(ServerRequestInterface::class),
            null
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $middleware = $this->prophesize(DelegateInterface::class);
        $middleware->process(new TypeToken(ServerRequestInterface::class))->willReturn(null);

        $this->assertInstanceOf(ResponseInterface::class, $app->process($request->reveal(), $middleware->reveal()));
    }
}
