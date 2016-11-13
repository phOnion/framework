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

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Http\Middleware\Exceptions\MiddlewareException;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class DelegatorTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionWhenMiddlewareDoesNotImplementRequiredInterfaces()
    {
        $this->expectException(\TypeError::class);

        new Delegate('foobar');
    }

    public function testInvocationOfProperFrameWithoutNext()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $stream = $this->prophesize(StreamInterface::class);
        $stream->isSeekable()->willReturn(true);
        $stream->tell()->willReturn(0);
        $stream->getSize()->willReturn(15);
        $stream->seek(15)->willReturn(null);
        $response->getBody()->willReturn($stream->reveal());
        $middleware->process(
            new AnyValueToken(), // Fails for some reason when using `Argument::type`
            new AnyValueToken()
        )->willReturn($response->reveal());

        $frame = new Delegate($middleware->reveal(), $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertSame(
            $response->reveal(),
            $frame->process($request->reveal())
        );
    }

    public function testInvocationExceptionWhenNoResponseInterfaceIsReturned()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware->process(
            new AnyValueToken(), // Fails for some reason when using `Argument::type`
            new AnyValueToken()
        )->willReturn(null);

        $frame = new Delegate($middleware->reveal(), $this->prophesize(DelegateInterface::class)->reveal());

        $this->expectException(\TypeError::class);
        $frame->process($request->reveal());
    }
}
