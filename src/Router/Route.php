<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use GuzzleHttp\Psr7\Response;
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

    /** @var string[] $parameters */
    private $parameters = [];

    private $consuming = [];
    private $producing = [];

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
        if ($this->handler === null) {
            throw new \RuntimeException(
                "No handler provided for route {$this->getName()}"
            );
        }
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

    public function getConsumed(string $kind): array
    {
        return $this->consuming[$kind] ?? [];
    }

    public function getProduced(string $kind): array
    {
        return $this->producing[$kind] ?? [];
    }

    public function hasName(): bool
    {
        return $this->name !== $this->getPattern();
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array(strtolower($method), $this->methods, true);
    }

    public function isConsuming(string $kind, string $type): bool
    {
        return $this->consuming === [] || (
            isset($this->consuming[$kind]) && in_array($type, $this->consuming[$kind], true)
        );
    }

    public function isProducing(string $kind, string $type): bool
    {
        return $this->producing === [] || (
            isset($this->producing[$kind]) && in_array($type, $this->producing[$kind], true)
        );
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

    public function withProduced(string $kind, array $types): self
    {
        $self = clone $this;
        $self->producing[$kind] = $types;

        return $self;
    }

    public function withConsumed(string $kind, array $types): self
    {
        $self = clone $this;
        $self->consuming[$kind] = $types;

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

        $varyHeaders = [];

        if ($request->hasHeader('content-type')) {
            foreach ($this->getConsumed('content') as $type) {
                if (stripos($request->getHeaderLine('content-type'), $type) === false) {
                    return new Response(406);
                }
            }
        }

        $languages = $this->getProduced('language');
        $content = $this->getProduced('content');
        $charset = $this->getProduced('charset');
        $encoding = $this->getProduced('encoding');

        if (!empty($languages) && $request->hasHeader('accept-language')) {
            $accept = new Accept('accept-language', $request->getHeaderLine('accept-language'));

            $accepted = [];
            foreach ($languages as $language) {
                if ($accept->supports($language)) {
                    $accepted[$language] = $accept->getPriority($language);
                }
            }

            $request = $request->withAttribute('language', $accepted);
            $varyHeaders[] = 'accept-language';
        }

        if (!empty($content) && $request->hasHeader('accept')) {
            $accept = new Accept('accept', $request->getHeaderLine('accept'));

            $accepted = [];
            foreach ($content as $type) {
                if ($accept->supports($type)) {
                    $accepted[$type] = $accept->getPriority($type);
                }
            }

            $request = $request->withAttribute('content', $accepted);
            $varyHeaders[] = 'accept';
        }

        if (!empty($charset) && $request->hasHeader('accept-charset')) {
            $accept = new Accept('accept-charset', $request->getHeaderLine('accept-charset'));

            $accepted = [];
            foreach ($charset as $type) {
                if ($accept->supports($type)) {
                    $accepted[$type] = $accept->getPriority($type);
                }
            }

            $request = $request->withAttribute('charset', $accepted);
            $varyHeaders[] = 'accept-charset';
        }

        if (!empty($encoding) && $request->hasHeader('accept-encoding')) {
            $accept = new Accept('accept-encoding', $request->getHeaderLine('accept-encoding'));

            $accepted = [];
            foreach ($encoding as $type) {
                if ($accept->supports($type)) {
                    $accepted[$type] = $accept->getPriority($type);
                }
            }

            $request = $request->withAttribute('encoding', $accepted);
            $varyHeaders[] = 'accept-encoding';
        }

        return $this->getRequestHandler()
            ->handle($request->withAttribute('route', $this))
            ->withHeader('vary', $varyHeaders);
    }
}
