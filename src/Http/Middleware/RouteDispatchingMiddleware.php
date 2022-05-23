<?php

declare(strict_types=1);

namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Router\Router;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteDispatchingMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Router $router)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->match($request);

        return $route->getAction()(
            $request->withAttribute(RouteInterface::class, $route)
                ->withAttribute('route', $route),
            $handler
        );
    }
}
