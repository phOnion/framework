<?php

declare(strict_types=1);

namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Http\Emitter\Interfaces\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseEmitterMiddleware implements MiddlewareInterface
{
    private $emitter;
    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $this->emitter->emit($response);

        return $response;
    }
}
