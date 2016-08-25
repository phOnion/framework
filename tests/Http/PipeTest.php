<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Http
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Http;

use Onion\Framework\Http\Middleware\Pipe;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PipeTest extends \PHPUnit_Framework_TestCase
{
    protected $request;
    protected function setUp()
    {
        $this->request = $this->prophesize(RequestInterface::class);
    }

    public function testExceptionWhenNoMiddlewareIsAvailable()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nothing to do');

        $pipe = new Pipe();
        $pipe->handle($this->request->reveal());
    }

    public function testAlignmentOfFrames()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->isSeekable()->willReturn(false);
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($stream->reveal());
        $middleware1 = $this->prophesize(MiddlewareInterface::class);
        $middleware1->process(Argument::any(), Argument::type(FrameInterface::class))->willReturn($response->reveal());
        $middleware2 = $this->prophesize(MiddlewareInterface::class);
        $middleware2->process(Argument::any(), null)->willReturn($response->reveal());

        $pipe = new Pipe([$middleware1->reveal(), $middleware2->reveal()]);
        $this->assertInstanceOf(ResponseInterface::class, $pipe->handle($this->request->reveal()));
    }

    public function testExceptionWhenInitializing()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Pipe())->initialize(null);
    }

    public function testExceptionWhenInvalidMiddlewareIsPassedToFrame()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nothing to do');
        $badMiddleware = function () {
        };

        $pipe = new Pipe([$badMiddleware]);
        $pipe->handle($this->request->reveal());
    }

    public function testCallToNextOnExceptionOnErrorInTheStack()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OK');

        $frame = $this->prophesize(FrameInterface::class);
        $frame->next(Argument::type(RequestInterface::class))
            ->willThrow(new \Exception('OK'));

        $pipe = new Pipe([null]);

        $pipe->process($this->prophesize(RequestInterface::class)->reveal(), $frame->reveal());
    }
}
