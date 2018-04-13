<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

abstract class Route implements RouteInterface, RequestHandlerInterface
{
    private $name;
    private $pattern;
    private $handler;
    private $methods;

    private $parameters = [];

    public function __construct(string $pattern, string $name = null)
    {
        $this->pattern = $this->parse($pattern);
        $this->name = $name ?? $pattern;
    }

    public function getName(): ?string
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

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array($method, $this->methods);
    }

    public function withMethods(iterable $methods): self
    {
        $self = clone $this;
        $self->methods = $methods;

        return $self;
    }

    public function withRequestHandler(RequestHandlerInterface $requestHandler): self
    {
        $self = clone $this;
        $self->handler = $requestHandler;

        return $self;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getRequestHandler()->handle($request);
    }
}
