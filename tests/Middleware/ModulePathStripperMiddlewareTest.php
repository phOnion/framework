<?php

namespace Tests\Middleware;

use Onion\Framework\Http\Delegate;
use Psr\Http\Message;
use Prophecy\Argument;

class ModulePathStripperMiddlewareTest
{
    public function testRequestPathChangeWhenPassingThroughMiddleware()
    {
        $middleware1 = new ModulePathStripperMiddleware('/test');
        $delegate = new Delegate([]);

        $uri = $this->prophesize(Message\UriInterface::class);
        $uri->getPath()->willReturn('/test/root')->shouldNotBeCalled();
        $uri->withPath('/root')->shouldNotBeCalled();

        $request = $this->propesize(Message\ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal())->shouldBeCalled();
        $request->withUri(Argument::that(function ($result) {
            return ($result instanceof Message\UriInterface);
        }))->shouldBeCalled();

        // $this->delegate->process($request->reveal());
        $this->assertInstanceOf(
            Message\ResponseInterface::class,
            $middleware1->process($request->reveal(), $delegate->reveal())
        );
        $uri->withPath('/root')->shouldHaveNotBeenCalled();
    }
}
