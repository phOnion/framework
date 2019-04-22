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
        $route = $this->resolver->resolve(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        $resolution = $route->handle($request->withAttribute('route', $route));
        $response = $handler->handle($request);

        foreach ($resolution->getHeaders() as $header => $value) {
            if ($response->hasHeader($header)) {
                $response->withAddedHeader($header, $value);
            }
            if (!$response->hasHeader($header)) {
                $response = $response->withHeader($header, $value);
            }
        }

        return $response->withStatus($resolution->getStatusCode())
            ->withBody($resolution->getBody())
            ->withProtocolVersion($resolution->getProtocolVersion());
    }
}
