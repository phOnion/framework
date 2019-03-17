<?php
namespace Onion\Framework\Router\Strategy;

use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

class CompiledRegexStrategy implements ResolverInterface
{
    /** @var RouteInterface[] $routes */
    private $routes = [];

    /**
     * @var RouteInterface[] $routes List of defined routes
     * @var int $maxGroupCount Maximum number of groups
     */
    public function __construct(array $routes, int $maxGroupCount = 10)
    {
        $compiledRoutes = [];
        foreach ($routes as $route) {
            foreach ($this->compile($route->getPattern()) as $pattern => $params) {
                assert(
                    !isset($compiledRoutes[$pattern]),
                    new \LogicException(sprintf(
                        'Compilation of %s duplicates an already existing pattern',
                        $route->getName()
                    ))
                );

                $compiledRoutes[$pattern] = [$route, $params];
            }
        }
        $sections = round(count($compiledRoutes)/$maxGroupCount)+1;

        for ($i=0; $i<$sections; $i++) {
            $segments = [];
            $handlers = [];
            $length = $maxGroupCount;
            foreach ($compiledRoutes as $key => $route) {
                $expansion = str_repeat('()', $length);
                $segments[] = "{$key}{$expansion}";
                $index = ($length + count($route[1]));
                $handlers[$index] = $route;

                $length--;
                unset($compiledRoutes[$key]);
                if ($length === 0) {
                    break;
                }
            }

            $pattern = '(?|' . implode('|', $segments) . ')';

            $this->routes[$pattern] = $handlers;
        }
    }

    public function resolve(string $method, string $path): RouteInterface
    {
        $route = $this->match($path, $params);

        if ($route === null) {
            throw new NotFoundException("No match for '{$path}' found");
        }

        if (!$route->hasMethod($method)) {
            throw new MethodNotAllowedException($route->getMethods());
        }

        return $route->withParameters($params);
    }

    private function compile(string $pattern): array
    {
        $segments = explode('/', trim($pattern, '/'));
        $params = [];
        $patterns = [];
        $path = '';

        foreach ($segments as $segment) {
            if (preg_match(self::PARAM_REGEX, $segment, $matches)) {
                if (isset($matches['conditional']) && $matches['conditional'] !== '') {
                    $patterns[$path] = $params;
                }

                $params[] = trim($matches['name']);
                $path .= '/(' . trim($matches['pattern'] ?? '[^/]') . ')';
                if (isset($matches['conditional']) && $matches['conditional'] !== '') {
                    $patterns[$path] = $params;
                }

                continue;
            } else {
                $path .= "/{$segment}";
            }
        }

        $patterns[$path] = $params;

        return array_reverse($patterns);
    }

    private function match(string $path, ?array &$params = []): ?RouteInterface
    {
        $params = $params ?? [];
        foreach ($this->routes as $pattern => $definition) {
            if (!preg_match('~^'.$pattern.'$~', $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $index = count($matches);

            $matches = array_filter($matches, function ($value) {
                return $value !== '';
            });

            foreach ($matches as $i => $match) {
                if ($match === '') {
                    continue;
                }

                $params[$definition[$index][1][$i]] = $match;
            }

            return $definition[$index][0];
        }

        return null;
    }
}
