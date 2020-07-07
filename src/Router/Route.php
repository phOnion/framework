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
    /** @var string $name */
    private $name;
    /** @var string $pattern */
    private $pattern;
    /** @var RequestHandlerInterface|null $handler */
    private $handler = null;
    /** @var string[] */
    private $methods = [];
    /** @var bool[] $headers*/
    private $headers = [];
    /** @var string[][] $preload */
    private $preload = [];

    /** @var string[] $parameters */
    private $parameters = [];

    public function __construct(string $pattern, ?string $name = null)
    {
        $this->pattern = $pattern;
        $this->name = $name ?? $pattern;
    }

    public function getName(): string
    {
        return $this->name;
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
        return $this->name !== $this->getPattern();
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

    public function withPreload(string $link, array $params): RouteInterface
    {
        $self = clone $this;
        $self->preload[$link] = $params;

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

        if ($request->hasHeader('accept')) {
            $accept = new Accept('accept', $request->getHeaderLine('accept'));
            $request = $request->withAttribute('content', $accept);
        }

        if ($request->hasHeader('accept-encoding')) {
            $accept = new Accept('accept-encoding', $request->getHeaderLine('accept-encoding'));
            $request = $request->withAttribute('encoding', $accept);
        }

        if ($request->hasHeader('accept-charset')) {
            $accept = new Accept('accept-charset', $request->getHeaderLine('accept-charset'));
            $request = $request->withAttribute('charset', $accept);
        }

        if ($request->hasHeader('accept-language')) {
            $accept = new Accept('accept-language', $request->getHeaderLine('accept-language'));
            $request = $request->withAttribute('language', $accept);
        }

        $response = $this->getRequestHandler()
            ->handle($request->withAttribute('route', $this));

        foreach ($this->preload as $link => $props) {
            $response = $response->withAddedHeader(
                'Link',
                "<{$link}>; " . implode('; ', array_map(function ($key) use ($props) {
                    return "{$key}={$props[$key]}";
                }, array_keys($props)))
            );
        }

        return $response;
    }
}
