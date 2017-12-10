<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Psr\Http\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Server\MiddlewareInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Http\Middleware\RequestHandler;

/**
 * Class Router
 *
 * @package Onion\Framework\Router
 */
class Router implements RouterInterface, MiddlewareInterface
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
    public function addRoute(RouteInterface $route): RouterInterface
    {
        $self = clone $this;
        assert(
            !array_key_exists($route->getName(), $this->routes),
            new \InvalidArgumentException(sprintf(
                'Route "%s" overlaps with another route using the same or similar pattern',
                $route->getPattern()
            ))
        );

        $self->routes[$route->getName()] = $route;

        return $self;
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
     * @param RequestHandlerInterface|null $requestHandler
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
    public function process(ServerRequestInterface $request, ?RequestHandler $requestHandler = null): ResponseInterface
    {
        $route = $this->match($request->getMethod(), $request->getUri());
        foreach ($route->getParameters() as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $route->getrequestHandler()->process($request);
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
        foreach ($this->routes as $route) {
            if (($matches = $this->getMatcher()->match($route->getPattern(), $uri->getPath())) !== [false]) {
                if (($methods = $route->getMethods()) !== [] && !in_array(strtoupper($method), $methods, true)) {
                    throw new Exceptions\MethodNotAllowedException($route->getMethods());
                }

                return $route->hydrate([
                    'parameters' => array_filter($matches, function ($key) {
                        return !is_int($key);
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
