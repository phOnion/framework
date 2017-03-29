<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Router
 *
 * @package Onion\Framework\Router
 */
class Router implements Interfaces\RouterInterface, MiddlewareInterface
{
    /**
     * @var RouteInterface[]
     */
    protected $routes = [];
    /**
     * @var MatcherInterface
     */
    private $matcher;

    /**
     * Router constructor.
     *
     * @param MatcherInterface $matcher
     */
    public function __construct(Interfaces\MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException When adding a duplicate pattern
     */
    public function addRoute(RouteInterface $route)
    {
        assert(
            !array_key_exists($route->getName(), $this->routes),
            new \InvalidArgumentException(sprintf(
                'Route "%s" overlaps with another route using the same or similar pattern',
                $route->getPattern()
            ))
        );

        $this->routes[$route->getName()] = $route;
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     */
    public function getRouteByName(string $name, array $params = []): string
    {
        assert(
            array_key_exists($name, $this->routes),
            new \InvalidArgumentException(sprintf('No route identified by "%s"', $name))
        );

        $route = $this->routes[$name];
        $pattern = $route->getPattern();
        foreach ($params as $param => $value) {
            $pattern = preg_replace(sprintf('~(\(\?P\<%s\>.*)~i', $param), $value, $pattern);
        }

        assert(
            strpos($pattern, '(?P<') === false,
            new \InvalidArgumentException(
                'Unable to create route from provided parameters'
            )
        );

        return $pattern;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface|null $delegate
     *
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     * @throws \Onion\Framework\Router\Interfaces\Exception\NotFoundException
     * @throws \Onion\Framework\Router\Interfaces\Exception\NotAllowedException
     * @throws \Onion\Framework\Router\Exceptions\NotFoundException
     * @throws \Onion\Framework\Router\Exceptions\MethodNotAllowedException
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate = null): ResponseInterface
    {
        $route = $this->match($request->getMethod(), $request->getUri());
        foreach ($route->getParameters() as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $route->getDelegate()->process($request);
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
     * @return \Onion\Framework\Hydrator\Interfaces\HydratableInterface|RouteInterface
     */
    public function match(string $method, Message\UriInterface $uri): RouteInterface
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if (($matches = $this->getMatcher()->match($route->getPattern(), $uri->getPath())) !== [false]) {
                if (!in_array($method, $route->getMethods(), true)) {
                    throw new Exceptions\MethodNotAllowedException($route->getMethods());
                }

                return $route->hydrate([
                    'parameters' => array_filter((array)$matches, function ($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY)
                ]);
            }
        }

        throw new Exceptions\NotFoundException(sprintf(
            'No route available to handle "%s"',
            $uri->getPath()
        ));
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->routes);
    }

    /**
     * @return MatcherInterface
     */
    private function getMatcher(): Interfaces\MatcherInterface
    {
        return $this->matcher;
    }
}
