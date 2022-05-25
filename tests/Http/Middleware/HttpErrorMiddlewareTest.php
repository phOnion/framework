<?php

namespace Tests\Http\Middleware;

use Onion\Framework\Http\Middleware\HttpErrorMiddleware;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Prophecy\Argument;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class HttpErrorMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    private $handler;
    private $request;

    use ProphecyTrait;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('OPTIONS');
        $uri = $this->prophesize(UriInterface::class);
        $uri->getHost()->willReturn('example.com');
        $uri->__toString()->willReturn('example.com');
        $request->getUri()->willReturn($uri->reveal());
        $this->request = $request;
    }

    public function withLoggerProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testAuthorizationException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;

        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('authorization'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('www-authenticate'));
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testProxyAuthorizationException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;

        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('proxy-authorization'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(407, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('proxy-authenticate'));
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testConditionalMatchException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-match'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testConditionalNonMatchException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-none-match'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testConditionalModifiedSinceException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-modified-since'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testConditionalUnmodifiedSinceException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-unmodified-since'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testConditionalRangeException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-range'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testCustomHeaderException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('x-custom'));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testNotFoundException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new NotFoundException());

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testUnsupportedMethodException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MethodNotAllowedException(['get', 'head']));

        $logger?->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame('get, head', $response->getHeaderLine('allow'));
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testNotImplementedException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \BadMethodCallException());
        $this->request->getMethod()->willReturn('GET');

        $logger?->warning(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledTimes(2);
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(503, $response->getStatusCode());

        $this->request->getMethod()->willReturn('post');
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(501, $response->getStatusCode());
    }

    /**
     * @dataProvider withLoggerProvider()
     */
    public function testUnknownErrorException($withLogger)
    {
        $logger = $withLogger ? $this->prophesize(LoggerInterface::class) : null;
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \Exception());

        $logger?->critical(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger?->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(500, $response->getStatusCode());
    }
}
