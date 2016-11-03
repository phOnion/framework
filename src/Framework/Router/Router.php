<?php
declare(strict_types=1);
namespace Onion\Framework\Router;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Psr\Http\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router implements Interfaces\RouterInterface, ServerMiddlewareInterface
{
    /**
     * @var ParserInterface
     */
    protected $parser;
    /**
     * @var array[]
     */
    protected $routes = [];
    /**
     * @var MatcherInterface
     */
    private $matcher;

    public function __construct(Interfaces\ParserInterface $parser, Interfaces\MatcherInterface $matcher)
    {
        $this->parser = $parser;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException When adding a duplicate pattern
     */
    public function addRoute(
        string $pattern,
        DelegateInterface $handler,
        array $methods,
        string $name = null
    ) {
        array_walk($methods, function (&$value) {
            $value = strtoupper($value);
        });

        $route = [
            $this->getParser()->parse($pattern),
            $handler,
            $methods,
            [],
        ];
        $name = $name ?? $pattern;

        assert(
            !array_key_exists($name, $this->routes),
            new \InvalidArgumentException(sprintf(
                'Route "%s" overlaps with another route using the same name and/or pattern',
                $name
            ))
        );


        $this->routes[$name] = $route;
        uasort($this->routes, function ($left, $right) {
            return strlen($left[0])<=>strlen($right[0]);
        });
    }

    private function getParser(): Interfaces\ParserInterface
    {
        return $this->parser;
    }

    public function getRouteByName(string $name, array $params = []): string
    {
        assert(
            array_key_exists($name, $this->routes),
            new \InvalidArgumentException(sprintf('No route identified by "%s"', $name))
        );

        $route = $this->routes[$name];
        $pattern = $route[0];
        foreach ($params as $param => $value) {
            $pattern = preg_replace(
                sprintf('~(\(\?P\<%s\>.*)~i', $param),
                $value,
                $pattern
            );
        }

        assert(
            preg_match('~^(?:' . $route[0] . ')$~x', $pattern) !== 0,
            new \InvalidArgumentException(
                'Unable to create route from provided parameters'
            )
        );

        return $pattern;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate = null): ResponseInterface
    {
        /**
         * @var array[] $route
         */
        $route = $this->match($request->getMethod(), $request->getUri());

        foreach ($route[3] as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $route[1]->process($request);
    }

    /**
     * Performs a match against the declared routes trying to match
     * them to the URI and check if they support the current request
     * method
     *
     * @param string               $method Current request method
     * @param Message\UriInterface $uri    Current request URI
     *
     * @throws Exceptions\MethodNotAllowedException|Interfaces\Exception\NotAllowedException If
     * the matched route does not support the current request method
     * @throws Exceptions\NotFoundException|Interfaces\Exception\NotFoundException If there
     * is no route found to handle the current request
     * @throws \RuntimeException if there is no parser defined for the router
     * @throws \InvalidArgumentException
     *
     * @covers Router::process
     *
     * @return array[]
     */
    public function match(string $method, Message\UriInterface $uri): array
    {
        $method = strtoupper($method);
        foreach ($this->routes as $pattern => $route) {
            assert(count($route) === 4, 'Route array must hold only 4 elements');

            assert(array_key_exists(0, $route), 'Array must contain index 0');
            assert(is_string($route[0]), 'Array index 0 must be a string');
            assert(array_key_exists(1, $route), 'Array must contain index 1');
            assert($route[1] instanceof DelegateInterface, 'Array index 1 must implement DelegateInterface');
            assert(array_key_exists(2, $route), 'Array must contain index 2');
            assert(is_array($route[2]), 'Array index 2 must be an array');
            assert($route[2] !== [], 'Array index 2 must be a non-empty array');
            assert(array_key_exists(3, $route), 'Array must contain index 3');
            assert(is_array($route[3]), 'Array index 3 must be an array');


            if (($matches = $this->getMatcher()->match($route[0], $uri->getPath())) !== [false]) {
                if (!in_array($method, $route[2], true)) {
                    throw new Exceptions\MethodNotAllowedException($route[2]);
                }

                $route[3] = array_filter((array)$matches);
                return $route;
            }
        }

        throw new Exceptions\NotFoundException(sprintf(
            'No route available to handle "%s"',
            $uri->getPath()
        ));
    }

    private function getMatcher(): Interfaces\MatcherInterface
    {
        return $this->matcher;
    }
}
