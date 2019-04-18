<?php
namespace Onion\Framework\Router\Strategy;

use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

class CompiledRegexStrategy implements ResolverInterface
{
    /** @var array $routes */
    private $routes = [];

    /**
     * @var RouteInterface[] $routes List of defined routes
     * @var int $maxGroupCount Maximum number of groups
     */
    public function __construct(iterable $routes, int $maxGroupCount)
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
        while (!empty($compiledRoutes)) {
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
                if (!$length) {
                    break;
                }
            }

            $pattern = '(?|' . implode('|', $segments) . ')';

            $this->routes[$pattern] = $handlers;
        }
    }

    public function resolve(string $method, string $path): RouteInterface
    {
        $params = [];
        $route = $this->match($path, $params);

        if ($route === null) {
            throw new NotFoundException("No match for '{$path}' found");
        }

        if (!$route->hasMethod($method)) {
            throw new MethodNotAllowedException($route->getMethods());
        }

        return $route->withParameters($params ?? []);
    }

    private function compile(string $pattern): array
    {
        $segments = explode('/', trim($pattern, '/'));
        $params = [];
        $patterns = [];
        $path = '';

        foreach ($segments as $segment) {
            if (preg_match(self::PARAM_REGEX, $segment, $matches)) {
                if (isset($matches['conditional'])) {
                    $patterns[!empty($path) ? $path : '/'] = $params;
                }

                $params[] = trim($matches['name']);
                $path .= '/(' . (!empty($matches['pattern']) ? $matches['pattern'] : '[^/]+') . ')';
                $patterns[$path] = $params;

                continue;
            }

            $path .= "/{$segment}";
        }

        $patterns[$path] = $params;

        return array_reverse($patterns);
    }

    private function match(string $path, array &$params = []): ?RouteInterface
    {
        foreach ($this->routes as $pattern => $definition) {
            if (!preg_match('~^'.$pattern.'$~', $path, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            array_shift($matches);
            $index = count($matches);

            $matches = array_filter($matches, function ($value) {
                return $value[0] !== '';
            });

            foreach ($matches as $i => $match) {
                $params[$definition[$index][1][$i]] = $match[0];
            }

            return $definition[$index][0];
        }

        return null;
    }
}
