<?php
namespace Tests\Http\Middleware;

use Onion\Framework\Http\Emitter\Interfaces\EmitterInterface;
use Onion\Framework\Http\Middleware\ResponseEmitterMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseEmitterMiddlewareTest extends TestCase
{
    public function testConstruction()
    {
        $emitter = $this->prophesize(EmitterInterface::class);
        $emitter->emit(new TypeToken(ResponseInterface::class))
            ->shouldBeCalledOnce();
        $request = $this->prophesize(ServerRequestInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(new TypeToken(ServerRequestInterface::class))
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal())
            ->shouldBeCalledOnce();

        $middleware = new ResponseEmitterMiddleware($emitter->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $middleware->process(
            $request->reveal(),
            $handler->reveal()
        ));
    }
}
