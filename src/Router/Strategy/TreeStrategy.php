<?php
namespace Onion\Framework\Router\Strategy;

use function Onion\Framework\Common\merge;
use function Onion\Framework\Common\normalize_tree_keys;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

class TreeStrategy implements ResolverInterface
{
    /** @var RouteInterface[] $routes */
    private $routes = [];

    /**
     * @var RouteInterface[] $routes
     */
    public function __construct(array $routes)
    {
        foreach ($routes as $route) {
            $this->routes[$route->getPattern()] = $route;
        }

        $this->routes = normalize_tree_keys($this->routes, '/');
    }

    public function resolve(string $method, string $path): RouteInterface
    {
        $params = [];
        $route = $this->match($this->routes, explode('/', trim($path, '/')), $params);

        if ($route === null) {
            throw new NotFoundException("No match for '{$path}' found");
        }

        if (!$route->hasMethod($method)) {
            throw new MethodNotAllowedException($route->getMethods());
        }

        return $route->withParameters(array_filter($params, function ($key) {
            return !is_integer($key);
        }, ARRAY_FILTER_USE_KEY));
    }

    private function match(array $routes, array $parts, array &$params = []): ?RouteInterface
    {
        $part = array_shift($parts);

        foreach ($routes as $segment => $remaining) {
            $compiled = $this->compile($segment);

            foreach ($compiled as $segment => $param) {
                $segment = trim($segment, '/');
                if (preg_match("~^{$segment}$~i", $part, $matches, PREG_OFFSET_CAPTURE) > 0) {
                    foreach ($param as $index => $key) {
                        $params[$key] = $matches[$index][0];
                    }

                    if (is_array($remaining)) {
                        return $this->match($remaining, $parts, $params);
                    }

                    return $remaining;
                }
            }
        }

        return null;
    }

    private function compile(string $pattern): array
    {
        $segments = explode('/', $pattern);
        $params = [];
        $patterns = [];
        $path = '';

        foreach ($segments as $index => $segment) {
            if ($segment === '*') {
                $segment = "{{$index}}";
            }
            $matched = preg_match(self::PARAM_REGEX, $segment, $matches);
            if ($matched) {
                $params[] = $matches['name'];
                $path = "{$path}/(" . (!empty($matches['pattern']) ? $matches['pattern'] : '[^/]+') . ')';
                $patterns[$path] = $params;
            }

            if (!$matched) {
                $path = "{$path}/{$segment}";
            }
        }

        $patterns[$path] = $params;

        return $patterns;
    }
}
