<?php
namespace Onion\Framework\Router\Strategy;

class CompiledRegexStrategy
{
    private const PARAM_REGEX = '~(\{(?P<name>[^\:\}]+)(?:\:(?P<pattern>[^\}]+))?\}(?P<conditional>\?)?+)+~iuU';
    private $routes = [];

    public function __construct(array $routes, int $groupCount = 10)
    {
        $compiledRoutes = [];
        foreach ($routes as $route) {
            foreach ($this->compile($route->getPattern()) as $pattern => $params) {
                if (isset($compiledRoutes[$pattern])) {
                    throw new \LogicException(sprintf('Compiled route %s duplicates %s',
                        $route->getName(),
                        $compiledRoutes[$pattern][0]->getName()
                    ));
                }

                $compiledRoutes[$pattern] = [$route, $params];
            }
        }
        $sections = round(count($compiledRoutes)/$groupCount)+1;

        for ($i=0; $i<$sections; $i++) {
            $segments = [];
            $handlers = [];
            $length = $groupCount;
            foreach ($compiledRoutes as $key => $route) {
                $expansion = str_repeat('()', $length);
                $segments[] = "{$key}{$expansion}";
                $index = ($length + count($route[1]));
                if (isset($handlers[$index])) {
                    throw new \RuntimeException("Possible route conflict");
                }
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

    public function resolve(string $method, string $path)
    {
        foreach ($this->routes as $pattern => $route) {
            if (!preg_match('~^'.$pattern.'$~', $path, $matches)) {
                continue;
            }

            $params = [];
            array_shift($matches);
            $index = count($matches);

            $matches = array_filter($matches, function ($value) {
                return $value !== '';
            });

            foreach ($matches as $i => $match) {
                if ($match === '') {
                    continue;
                }

                $params[$route[$index][1][$i]] = $match;
            }

            return $route[$index][0]->withParameters($params);
        }
    }

    private function compile(string $pattern)
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
}
