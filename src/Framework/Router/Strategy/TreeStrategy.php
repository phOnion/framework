<?php
namespace Onion\Framework\Router\Strategy;

use function Onion\Framework\merge;



class TreeStrategy
{
    private $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function resolve(string $method, string $path)
    {
        $parts = explode('/', trim($path, '/'));

        $params = [];
        $match = $this->match($this->routes, $parts, $params);

        if ($match === null) {
            throw new \RuntimeException('No match found');
        }
        $params = array_filter($params, function ($key) {
            return !is_integer($key);
        }, ARRAY_FILTER_USE_KEY);

        if (isset($match['methods']) && !in_array($method, $match['methods'])) {
            throw new \BadMethodCallException("Method {$method} not allowed");
        }

        return [$match, $params];
    }

    private function match(array $routes, array $parts, &$params = []): ?array
    {
        $part = array_shift($parts);

        if ($part === null) {
            return $routes;
        }

        foreach ($routes as $segment => $remaining) {
            if (preg_match("/^{$segment}$/i", $part, $matches) > 0) {
                $params = merge($params ?? [], $matches);
                return $this->match($remaining, $parts, $params);
            }
        }

        return null;
    }
}
