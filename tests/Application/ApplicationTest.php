<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests;

use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Application\Application;
use Prophecy\Argument;
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

        $this->emitter->emit(Argument::any())->willThrow(new \RuntimeException('OK'));
        $this->stack->process(
            Argument::type(ServerRequestInterface::class)
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
            Argument::type(RequestInterface::class),
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
        $this->stack->process(Argument::type(RequestInterface::class), null)
            ->willThrow(\Exception::class);

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $this->expectException(\Throwable::class);
        $app->process($this->prophesize(ServerRequestInterface::class)->reveal(), null);
    }

    public function testDelegateInvocationOnException()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->withAttribute()->willReturn(function () use ($request) {
            return $request->reveal();
        });
        $this->stack->process(Argument::type(RequestInterface::class), null)
            ->willThrow(\Exception::class);

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::any())->willThrow(new \Exception('Delegate Error'));


        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delegate Error');
        $app->process($request->reveal(), $delegate->reveal());
    }

    public function testApplicationFrameRun()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->willImplement(ServerRequestInterface::class);
        $this->stack->process(
            Argument::type(ServerRequestInterface::class),
            null
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $middleware = $this->prophesize(DelegateInterface::class);
        $middleware->process(Argument::type(ServerRequestInterface::class))->willReturn(null);

        $this->assertInstanceOf(ResponseInterface::class, $app->process($request->reveal(), $middleware->reveal()));
    }
}
