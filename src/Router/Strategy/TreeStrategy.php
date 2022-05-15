<?php

declare(strict_types=1);

namespace Onion\Framework\Router\Strategy;

use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

use function Onion\Framework\normalize_tree_keys;

class TreeStrategy implements ResolverInterface
{
    /** @var RouteInterface[] $routes */
    private $routes = [];

    /**
     * @param RouteInterface[] $routes
     */
    public function __construct(iterable $routes)
    {
        foreach ($routes as $route) {
            $pattern = str_replace('/', '~~', trim($route->getPattern(), '/'));
            foreach ($this->compile($pattern) as $pattern) {
                $this->routes[trim("{$pattern}~~@", '~')] = $route;
            }
        }

        $this->routes = normalize_tree_keys($this->routes, '~~');
    }

    public function resolve(string $method, string $path): RouteInterface
    {
        $params = [];
        $path = trim($path, '/') ?: '';
        $route = $this->match($this->routes, $path !== '' ? explode('/', $path) : [], $params);

        if ($route === null) {
            throw new NotFoundException("No match for '{$path}' found");
        }

        if (!$route->hasMethod($method)) {
            throw new MethodNotAllowedException($route->getMethods());
        }

        $parameters = [];
        foreach ($params as $name => $value) {
            $name = substr((string) $name, 1);
            if (!is_string($name) || is_numeric($name) || strlen($name) === 0) {
                continue;
            }

            $parameters[$name] = $value;
        }

        return $route->withParameters($parameters);
    }

    private function match(array $routes, array $parts, array &$params = []): ?RouteInterface
    {
        if ($parts === []) {
            return $routes['@'] ?? null;
        }

        $part = array_shift($parts);
        foreach ($routes as $segment => $remaining) {
            if (preg_match("~^{$segment}$~i", (string) $part, $matches) > 0) {
                foreach ($matches as $index => $value) {
                    $params[$index] = $value;
                }

                if (is_array($remaining)) {
                    return $this->match($remaining, $parts, $params);
                }
            }
        }

        return null;
    }

    private function compile(string $pattern): array
    {
        $segments = explode('~~', $pattern);
        $patterns = [];
        $path = '';

        foreach ($segments as $index => $segment) {
            if ($segment === '*') {
                $segment = "{{$index}}";
            }
            $matched = preg_match(self::PARAM_REGEX, $segment, $matches);

            if ($matched) {
                if (isset($matches['conditional'])) {
                    $patterns[] = $path;
                }

                $path = "{$path}~~(?P<_{$matches['name']}>" .
                    (!empty($matches['pattern']) ? $matches['pattern'] : '[^/]+') .
                    ')' . (isset($matches['conditional']) ? '?' : '');

                $patterns[] = trim($path, '~~');
            } else {
                $path = trim("{$path}~~{$segment}", '~~');
            }
        }

        $patterns[] = $path;

        return $patterns;
    }
}
