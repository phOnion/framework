<?php
namespace Tests\Http;

use Onion\Framework\Http\RequestHandler\RequestHandler as Delegate;
use Prophecy\Argument;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;

class DelegateTest extends \PHPUnit\Framework\TestCase
{
    public function testExceptionWhenMiddlewareDoesNotImplementRequiredInterfaces()
    {
        $this->expectException(\TypeError::class);

        $handler = new Delegate(['foobar']);
        $request = $this->prophesize(ServerRequestInterface::class);

        $handler->handle($request->reveal());
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
            $frame->handle($request->reveal())
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
        $frame->handle($request->reveal());
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
        $delegate->handle($request->reveal());
    }

    public function testDelegateErrorWhenNoResponseTemplateInjected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No base response provided');
        $request = $this->prophesize(ServerRequestInterface::class);
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware->process(
            new AnyValueToken(), // Fails for some reason when using `Argument::type`
            new AnyValueToken()
        )->willReturn($this->prophesize(ResponseInterface::class)->reveal());
        $delegate = new Delegate([$middleware->reveal()]);
        $delegate->handle($request->reveal());
        $delegate->handle($request->reveal());
    }

    public function testDelegateReturningResponse()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $delegate = new Delegate([], $this->prophesize(ResponseInterface::class)->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $delegate->handle($request->reveal()));
    }
}
