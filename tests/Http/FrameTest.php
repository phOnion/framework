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

use Onion\Framework\Http\Middleware\Frame;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FrameTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionWhenMiddlewareDoesNotImplementRequiredInterfaces()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware provided must implement MiddlewareInterface');

        new Frame('foobar');
    }

    public function testInvocationOfProperFrameWithoutNext()
    {
        $request = $this->prophesize(RequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $stream = $this->prophesize(StreamInterface::class);
        $stream->isSeekable()->willReturn(true);
        $stream->tell()->willReturn(0);
        $stream->getSize()->willReturn(15);
        $stream->seek(15)->willReturn(null);
        $response->getBody()->willReturn($stream->reveal());
        $middleware->process(
            Argument::any(), // Fails for some reason when using `Argument::type`
            Argument::any()
        )->willReturn($response->reveal());

        $frame = new Frame($middleware->reveal());

        $this->assertSame(
            $response->reveal(),
            $frame->next($request->reveal())
        );
    }

    public function testInvocationExceptionWhenNoResponseInterfaceIsReturned()
    {
        $request = $this->prophesize(RequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware->process(
            Argument::any(), // Fails for some reason when using `Argument::type`
            Argument::any()
        )->willReturn(null);

        $frame = new Frame($middleware->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response type is: NULL');
        $frame->next($request->reveal());
    }
}
