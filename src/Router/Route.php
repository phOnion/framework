<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Route implements RouteInterface
{
    private ?RequestHandlerInterface $handler = null;
    /** @var string[] */
    private array $methods = [];
    /** @var bool[] $headers*/
    private array $headers = [];

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

    public function getRequestHandler(): RequestHandlerInterface
    {
        assert($this->handler !== null, new \RuntimeException(
            "No handler provided for route {$this->getName()}"
        ));

        return $this->handler;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array(strtolower($method), $this->methods, true);
    }

    public function withMethods(array $methods): RouteInterface
    {
        $self = clone $this;
        foreach ($methods as $method) {
            $self->methods[] = strtolower($method);
        }

        return $self;
    }

    public function withRequestHandler(RequestHandlerInterface $requestHandler): RouteInterface
    {
        $self = clone $this;
        $self->handler = $requestHandler;

        return $self;
    }

    public function withHeaders(array $headers): RouteInterface
    {
        $self = clone $this;
        foreach ($headers as $header => $required) {
            $self->headers[strtolower($header)] = $required;
        }

        return $self;
    }

    public function withParameters(array $parameters): RouteInterface
    {
        $self = clone $this;
        $self->parameters = $parameters;

        return $self;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->getHeaders() as $header => $required) {
            if ($required && !$request->hasHeader($header)) {
                throw new MissingHeaderException($header);
            }
        }

        if (!$this->hasMethod($request->getMethod())) {
            throw new MethodNotAllowedException($this->getMethods());
        }

        return $this->getRequestHandler()
            ->handle($request->withAttribute('route', $this));
    }
}
