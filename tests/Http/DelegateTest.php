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
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Http\Middleware\Exceptions\MiddlewareException;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class DelegateTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionWhenMiddlewareDoesNotImplementRequiredInterfaces()
    {
        $this->expectException(\TypeError::class);

        new Delegate('foobar');
    }

    public function testInvocationOfProperFrameWithoutNext()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
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

        $frame = new Delegate([$middleware->reveal()]);

        $this->assertSame(
            $response->reveal(),
            $frame->process($request->reveal())
        );
    }

    public function testInvocationExceptionWhenNoResponseInterfaceIsReturned()
    {

        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware->process(
            new AnyValueToken(), // Fails for some reason when using `Argument::type`
            new AnyValueToken()
        )->willReturn(null);

        $frame = new Delegate([$middleware->reveal()]);

        $this->expectException(\TypeError::class);
        $frame->process($request->reveal());
    }

    public function testInvokationWhenMiddlewareArrayIsInvalid()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->expectException(\TypeError::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $delegate = new Delegate(['bad-middleware']);
        $delegate->process($request->reveal());
    }

    public function testDelegateErrorWhenNoResponseTemplateInjected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No response template provided');
        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware->process(
            new AnyValueToken(), // Fails for some reason when using `Argument::type`
            new AnyValueToken()
        )->willReturn($this->prophesize(ResponseInterface::class)->reveal());
        $delegate = new Delegate([$middleware->reveal()]);
        $delegate->process($request->reveal());
        $delegate->process($request->reveal());
    }
}
