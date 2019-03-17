<?php
namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Router\Interfaces\ResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteDispatchingMiddleware implements MiddlewareInterface
{
    /** @var ResolverInterface $resolver */
    private $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $route = $this->resolver->resolve(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        $resolution = $route->handle($request->withAttribute('route', $route));

        foreach ($resolution->getHeaders() as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response->withStatus($resolution->getStatusCode())
            ->withBody($resolution->getBody())
            ->withProtocolVersion($resolution->getProtocolVersion());
    }
}
