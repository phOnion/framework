<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Closure;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Server\MiddlewareInterface;

class Route implements RouteInterface
{
    private ?Closure $action = null;
    /** @var string[] */
    private array $methods = [];

    /** @var string[] $parameters */
    private $parameters = [];

    public function __construct(
        private readonly string $pattern,
        private readonly ?string $name = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name ?? $this->pattern;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getAction(): Closure
    {
        assert($this->action !== null, new \RuntimeException(
            "No handler provided for route {$this->getName()}"
        ));

        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || \in_array(\strtolower($method), $this->methods, true);
    }

    public function withMethods(array $methods): RouteInterface
    {
        $self = clone $this;
        foreach ($methods as $method) {
            $self->methods[] = \strtolower($method);
        }

        return $self;
    }

    public function withAction(Closure|MiddlewareInterface $action): RouteInterface
    {
        $self = clone $this;
        $self->action = $action instanceof MiddlewareInterface ? $action->process(...) : $action;

        return $self;
    }

    public function withParameters(array $parameters): RouteInterface
    {
        $self = clone $this;
        $self->parameters = $parameters;

        return $self;
    }
}
