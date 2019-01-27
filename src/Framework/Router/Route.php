<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Route implements RouteInterface
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

    /** @var string[] $parameters */
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
        if ($this->handler === null) {
            throw new \RuntimeException(
                "No handler provided for route {$this->getName()}"
            );
        }
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
        return $this->name !== $this->getPattern();
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array(strtolower($method), $this->methods);
    }

    public function withMethods(iterable $methods): RouteInterface
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

    public function withHeaders(iterable $headers): RouteInterface
    {
        if ($headers instanceof \Iterator) {
            $headers = iterator_to_array($headers, true);
        }

        $self = clone $this;
        $self->headers = $headers;

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

        $response = $this->getRequestHandler()->handle($request);

        return $response;
    }
}
