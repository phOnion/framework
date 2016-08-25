<?php
/**
 * PHP Version 5.6.0
 *
 * @category Routing
 * @package  Onion\Framework\Router
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router;

use Onion\Framework\Interfaces;
use Onion\Framework\Interfaces\Router\ParserInterface;
use Onion\Framework\Interfaces\Router\RouteInterface;
use Psr\Http\Message;

class Router implements Interfaces\Router\RouterInterface
{
    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var RouteInterface
     */
    protected $routeRoot;
    /**
     * @var array
     */
    protected $routes = [];

    protected $namedRoutes = [];

    public function setRouteRootObject(RouteInterface $routeInterface)
    {
        $this->routeRoot = $routeInterface;

        return $this;
    }

    /**
     * @internal
     *
     * @throws \RuntimeException When no route root object is defined
     *
     * @return RouteInterface
     */
    public function getRouteRoot()
    {
        if (!$this->routeRoot instanceof RouteInterface) {
            throw new \RuntimeException(
                'Invalid root route object provided, must implement ' .
                    '"Onion\Framework\Interfaces\Routing\RouteInterface"'
            );
        }

        return clone $this->routeRoot;
    }

    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Retrieve the parser that is used by the route
     * @internal
     *
     * @throws \RuntimeException When there is no parser defined
     *
     * @return ParserInterface
     */
    public function getParser()
    {
        if ($this->parser === null) {
            throw new \RuntimeException(
                'No route parser provided to router'
            );
        }

        return $this->parser;
    }

    /**
     * Performs a match against the declared routes trying to match
     * them to the URI and check if they support the current request
     * method
     *
     * @param string               $method Current request method
     * @param Message\UriInterface $uri    Current request URI
     *
     * @throws Exceptions\MethodNotAllowedException|Interfaces\Router\Exception\NotAllowedException If
     * the matched route does not support the current request method
     * @throws Exceptions\NotFoundException|Interfaces\Router\Exception\NotFoundException If there
     * is no route found to handle the current request
     * @throws \RuntimeException if there is no parser defined for the router
     *
     * @return RouteInterface
     * @throws \InvalidArgumentException
     */
    public function match($method, Message\UriInterface $uri)
    {
        $method = strtoupper($method);
        foreach ($this->routes as $pattern => $route) {
            $matches = $this->getParser()->match(
                '~^(?:' . $pattern . ')$~x',
                $uri->getPath()
            );

            if ($matches !== false) {
                /**
                 * @var $route RouteInterface
                 */
                array_walk($matches, function (&$value, $index) {
                    $value = urldecode($value);
                    if (is_numeric($index)) {
                        $value = null;
                    }
                });

                if (!in_array($method, $route->getSupportedMethods(), true)) {
                    throw new Exceptions\MethodNotAllowedException(
                        $route->getSupportedMethods()
                    );
                }

                $route->setParams(array_filter((array)$matches));
                return $route;
            }
        }

        throw new Exceptions\NotFoundException(sprintf(
            'No route available to handle "%s"',
            $uri->getPath()
        ));
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException When adding a duplicate pattern
     */
    public function addRoute(
        array $methods,
        $pattern,
        $handler,
        $name = null
    ) {
    
        /**
         * @var $route RouteInterface
         */
        $route = $this->buildRoute($pattern, $handler);
        $route->setSupportedMethods($methods);

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }

        if (array_key_exists($name, $this->routes) ||
            array_key_exists($route->getPattern(), $this->routes)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Route "%s" overlaps with another route using the same name ' .
                    'and/or pattern that is already defined',
                $pattern
            ));
        }

        $this->routes[$route->getPattern()] = $route;
        uksort($this->routes, function ($left, $right) {
            return strlen($left)-strlen($right);
        });

        return $this;
    }

    /**
     *
     * @internal
     *
     * @param string $pattern the pattern of the route
     * @param array  $handler Handlers of the route
     *
     * @throws \RuntimeException if no parser is defined for the
     * router or no root object is defined
     *
     * @return Interfaces\Router\RouteInterface
     */
    protected function buildRoute($pattern, $handler)
    {
        $route = $this->getRouteRoot();
        $pattern = parse_url($pattern, PHP_URL_PATH);

        $parsedPattern = $this->getParser()
            ->parse($pattern);

        $route->setPattern($parsedPattern);
        $route->setMiddleware($handler);

        return $route;
    }

    public function getRouteByName($name, array $params = [])
    {
        if (!array_key_exists($name, $this->namedRoutes)) {
            throw new \InvalidArgumentException(sprintf('No route named "%s"', $name));
        }

        /**
         * @var $route Interfaces\Router\RouteInterface
         */
        $route = $this->namedRoutes[$name];
        $pattern = $route->getPattern();
        foreach ($params as $param => $value) {
            $pattern = preg_replace(
                sprintf('~(\(\?P\<%s\>.*)~i', $param),
                $value,
                $pattern
            );
        }

        if (preg_match('~^(?:' .$route->getPattern(). ')$~x', $pattern) === 0) {
            throw new \InvalidArgumentException(
                'Unable to create route from provided parameters'
            );
        }
        return $pattern;
    }

    public function count()
    {
        return count($this->routes);
    }
}
