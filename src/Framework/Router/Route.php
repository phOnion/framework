<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Route implements RouteInterface
{
    private $name;
    private $pattern;
    private $handler;
    private $methods = [];
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
        if ($methods instanceof \Iterator) {
            $methods = iterator_to_array($methods, false);
        }
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
        if ($headers instanceof \Iterator) {
            $headers = iterator_to_array($headers, true);
        }

        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->getRequestHandler()->handle($request);

        foreach ($this->getHeaders() as $header => $values) {
            assert(is_array($values), new \InvalidArgumentException(
                'Header\'s value must be an array of strings'
            ));

            foreach ($values as $value) {
                try {
                    $response = $response->withAddedHeader(
                        $header,
                        $this->substituteValues($value, $request->getQueryParams())
                    );
                } catch (\LogicException $ex) {
                    continue;
                }
            }
        }

        return $response;
    }

    private function substituteValues(string $pattern, $extra): string
    {
        if (strpos($pattern, '{') !== false) {
            $params = array_merge($this->getParameters(), $extra);
            preg_match_all(
                '~((?P<left>[a-zA-Z0-9_]+)(\:(?P<default>[a-zA-Z0-9_]+)?(?P<sign>[\-\+])?(?P<right>\d+)?))~',
                $pattern,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                if (!isset($params[$match['left']]) && !isset($match['default'])) {
                    continue;
                }
                $value = $params[$match['left']] ?? $match['default'];
                if (is_numeric($value) && $match['right']) {
                    switch ($match['sign']) {
                        case '+':
                            $value = (int) $value + (int) $match['right'];
                            break;
                        case '-':
                            $value = (int) $value - (int) $match['right'];
                            break;
                    }

                    if ($value <= 0) {
                        throw new \LogicException(
                            'Negative indexes do not make sense'
                        );
                    }
                }

                $pattern = str_replace("{{$match[0]}}", $value, $pattern);
            }
        }

        return $pattern;
    }
}
