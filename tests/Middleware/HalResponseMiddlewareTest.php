<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Middleware;


use Onion\Framework\Http\Middleware\Frame;
use Onion\Framework\Http\Response\RawResponse;
use Onion\Framework\Interfaces\Hal\StrategyInterface;
use Onion\Framework\Middleware\HalResponseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class HalResponseMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    protected $response;
    protected $resource;
    protected $request;
    protected $frame;

    protected function setUp()
    {
        $this->response = $this->prophesize(RawResponse::class);

        $this->request = $this->prophesize(ServerRequestInterface::class);

        $this->frame = $this->prophesize(Frame::class);
        $this->frame->next($this->request->reveal())->willReturn();
    }

    public function testEmpty406Response()
    {
        $middleware = new HalResponseMiddleware();
        $response = $this->prophesize(ServerRequestInterface::class);
        $this->frame->next($this->request->reveal())->willReturn($response->reveal());
        $this->assertSame($response->reveal(), $middleware->process($this->request->reveal(), $this->frame->reveal()));
    }

    public function testNegotiationByFileExtension()
    {
        $response = $this->response;
        $this->frame->next($this->request->reveal())->willReturn($response->reveal());

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('test.html');

        $this->request->getUri()->willReturn($uri->reveal());
        $dummyStrategy = $this->prophesize(StrategyInterface::class);
        $dummyStrategy->getSupportedExtension()->willReturn('html');
        $dummyStrategy->process($this->response->reveal())->willReturn($this->response->reveal());
        $middleware = new HalResponseMiddleware([$dummyStrategy->reveal()]);

        $this->assertSame($response->reveal(), $middleware->process($this->request->reveal(), $this->frame->reveal()));
    }

    public function testUnsuccessfulNegotiaionByFileName()
    {
        $response = $this->response;
        $this->frame->next($this->request->reveal())->willReturn($response->reveal());

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('test.json');

        $this->request->getUri()->willReturn($uri->reveal());
        $this->request->hasHeader('accept')->willReturn(false);
        $dummyStrategy = $this->prophesize(StrategyInterface::class);
        $dummyStrategy->getSupportedExtension()->willReturn('html');
        $dummyStrategy->process($this->response->reveal())->willReturn($this->response->reveal());
        $middleware = new HalResponseMiddleware([$dummyStrategy->reveal()]);

        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request->reveal(), $this->frame->reveal()));
        $this->assertSame(406, $middleware->process($this->request->reveal(), $this->frame->reveal())->getStatusCode());
    }

    public function testContentNegotiationByAcceptHeader()
    {
        $response = $this->response;
        $this->frame->next($this->request->reveal())->willReturn($response->reveal());

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('test.json');

        $this->request->getUri()->willReturn($uri->reveal());
        $this->request->hasHeader('accept')->willReturn(true);
        $this->request->getHeaderLine('accept')->willReturn('text/html');
        $dummyStrategy = $this->prophesize(StrategyInterface::class);
        $dummyStrategy->getSupportedExtension()->willReturn('x');
        $dummyStrategy->getSupportedTypes()->willReturn(['text/html']);
        $dummyStrategy->process($this->response->reveal())->willReturn($this->response->reveal());
        $middleware = new HalResponseMiddleware([$dummyStrategy->reveal()]);

        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request->reveal(), $this->frame->reveal()));
        $this->assertSame($this->response->reveal(), $middleware->process($this->request->reveal(), $this->frame->reveal()));
    }

    public function test406ResponseWithUnsupportedAcceptHeader()
    {
        $response = $this->response;
        $this->frame->next($this->request->reveal())->willReturn($response->reveal());

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('test.json');

        $this->request->getUri()->willReturn($uri->reveal());
        $this->request->hasHeader('accept')->willReturn(true);
        $this->request->getHeaderLine('accept')->willReturn('text/html');
        $dummyStrategy = $this->prophesize(StrategyInterface::class);
        $dummyStrategy->getSupportedExtension()->willReturn('x');
        $dummyStrategy->getSupportedTypes()->willReturn(['text/*']);
        $dummyStrategy->process($this->response->reveal())->willReturn($this->response->reveal());
        $middleware = new HalResponseMiddleware([$dummyStrategy->reveal()]);

        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request->reveal(), $this->frame->reveal()));
        $this->assertSame(406, $middleware->process($this->request->reveal(), $this->frame->reveal())->getStatusCode());
    }
}
