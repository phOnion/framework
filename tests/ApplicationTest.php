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

use Onion\Framework\Application;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $emitter;
    protected $stack;
    public function setUp()
    {
        $this->emitter = $this->prophesize(EmitterInterface::class);
        $this->stack = $this->prophesize(StackInterface::class);
    }

    public function testApplicationInvocation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OK');

        $this->emitter->emit(Argument::any())->willThrow(new \RuntimeException('OK'));
        $this->stack->handle(
            Argument::type(RequestInterface::class)
        )->willReturn(
            $this->prophesize(ResponseInterface::class)->reveal()
        );

        $app = new Application(
            $this->stack->reveal(),
            $this->emitter->reveal()
        );

        $app->run($this->prophesize(RequestInterface::class)->reveal());
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

        $this->assertNull($app->process($this->prophesize(RequestInterface::class)->reveal(), null));
    }

    public function testApplicationFrameRun()
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

        $middleware = $this->prophesize(FrameInterface::class);
        $middleware->next(Argument::type(RequestInterface::class))->willReturn(null);

        $this->assertNull($app->process($this->prophesize(RequestInterface::class)->reveal(), $middleware->reveal()));
    }
}
