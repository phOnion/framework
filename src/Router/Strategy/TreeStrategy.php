<?php
namespace Onion\Framework\Router\Strategy;

use function Onion\Framework\merge;
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
        $this->routes = $routes;
    }

    public function resolve(string $method, string $path): RouteInterface
    {
        $route = $this->match($this->routes, explode('/', trim($path, '/')), $params);

        if ($route === null) {
            throw new NotFoundException("No match for '{$path}' found");
        }

        if (!$route->hasMethod($method)) {
            throw new MethodNotAllowedException($route->getMethods());
        }

        $params = array_filter($params, function ($key) {
            return !is_integer($key);
        }, ARRAY_FILTER_USE_KEY);

        return $route->withParameters($params);
    }

    private function match(array $routes, array $parts, ?array &$params = []): ?RouteInterface
    {
        $part = array_shift($parts);

        if ($part === null) {
            return null;
        }

        foreach ($routes as $segment => $remaining) {
            if ($segment === '*') {
                $segment = '.*';
            }

            if (preg_match("/^{$segment}$/i", $part, $matches) > 0) {
                $params = merge($params ?? [], $matches);

                if ($remaining instanceof RouteInterface) {
                    return $remaining;
                }

                return $this->match($remaining, $parts, $params);
            }
        }

        return null;
    }
}
