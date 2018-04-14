<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

abstract class Route implements RouteInterface
{
    private $name;
    private $pattern;
    private $handler;
    private $methods;
    private $headers = [];

    private $parameters = [];

    public function __construct(string $pattern, string $name = null)
    {
        $this->pattern = $pattern;
        $this->name = $name ?? $pattern;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethods(): iterable
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    public function getParameters(): iterable
    {
        return $this->parameters;
    }

    public function getHeaders(): iterable
    {
        return $this->headers;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array($method, $this->methods);
    }

    public function withMethods(iterable $methods): RouteInterface
    {
        $self = clone $this;
        $self->methods = $methods;

        return $self;
    }

    public function withRequestHandler(RequestHandlerInterface $requestHandler): RouteInterface
    {
        $self = clone $this;
        $self->handler = $requestHandler;

        return $self;
    }

    public function withHeaders(iterable $headers): RouteInterface
    {
        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->getRequestHandler()->handle($request);
        $methods = $this->getMethods();
        if ($methods !== []) {
            $response->withAddedHeader('Access-Control-Allow-Methods: ' . strtoupper(implode(', ', $methods)));
        }

        if (!$response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $response->withAddedHeader(
                'Access-Control-Allow-Origin: ' . $request->getUri()->getAuthority() ?? '*'
            );
        }

        $response = $response->withAddedHeader('Access-Control-Allow-Credentials: true');
        $response = $response->withAddedHeader('Access-Control-Max-Age: 86400');

        foreach ($this->getHeaders() as $header => $values) {
            $response = $response->withAddedHeader($header, $values);
        }

        return $response;
    }
}
