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

    public function testAuthorizationException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('authorization'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('www-authenticate'));
    }

    public function testProxyAuthorizationException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('proxy-authorization'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(407, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('proxy-authenticate'));
    }

    public function testConditionalMatchException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-match'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testConditionalNonMatchException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-none-match'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testConditionalModifiedSinceException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-modified-since'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testConditionalUnmodifiedSinceException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-unmodified-since'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testConditionalRangeException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('if-range'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(428, $response->getStatusCode());
    }

    public function testCustomHeaderException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MissingHeaderException('x-custom'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNotFoundException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new NotFoundException());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUnsupportedMethodException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new MethodNotAllowedException(['get', 'head']));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->info(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame('get, head', $response->getHeaderLine('allow'));
    }

    public function testNotImplementedException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \BadMethodCallException());
        $this->request->getMethod()->willReturn('GET');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(503, $response->getStatusCode());

        $this->request->getMethod()->willReturn('post');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(501, $response->getStatusCode());
    }

    public function testUnknownErrorException()
    {
        $this->handler->handle(new AnyValueToken())
            ->willThrow(new \Exception());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(Argument::type('string'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $response = (new HttpErrorMiddleware(logger: $logger->reveal()))->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(500, $response->getStatusCode());
    }
}
