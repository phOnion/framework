<?php
namespace Onion\Framework\Router\Strategy;

class CompiledRegexStrategy
{
    private $routes = [];

    public function __construct(array $routes, int $groupCount = 10)
    {
        $compiledRoutes = [];
        foreach ($routes as $route) {
            foreach ($this->compile($route->getPattern()) as $pattern => $params) {
                $compiledRoutes[$pattern] = [$route, $params];
            }
        }
        $sections = round(count($compiledRoutes)/10)+1;

        for ($i=0; $i<$sections; $i++) {
            $handlers = [];
            $pattern = '(?|';
            $length = 0;
            foreach ($compiledRoutes as $key => $route) {
                $expansion = str_repeat('()', $length);
                $pattern .= "{$key}{$expansion}|";
                $index = ($length + count($route[1]));
                if (isset($handlers[$index])) {
                    throw new \RuntimeException("Possible route conflict");
                }
                $handlers[$index] = $route;

                $length++;
                if ($groupCount === $length) {
                    break;
                }
                unset($compiledRoutes[$key]);
            }

            $pattern = rtrim($pattern, '/|');
            $pattern .= ')';

            $this->routes[$pattern] = $handlers;
        }
    }

    public function resolve(string $method, string $path)
    {
        foreach ($this->routes as $pattern => $route) {
            if(!preg_match('~'.$pattern.'~', $path, $matches)) {
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

            return [$route[$index][0], $params];
        }
    }

    private function compile(string $pattern)
    {
        $patterns = [];
        $params = [];
        preg_match('~([^\{]+)~i', $pattern, $prefix);
        $partial = rtrim($prefix[0] ?? '', '/');
        preg_match_all('~(\{(?P<name>.*)(?:\:\s?(?P<pattern>.*))?\}(?P<conditional>\?)?+)+~iuU', $pattern, $matches, PREG_SET_ORDER);
        if (!empty($matches)) {
            foreach ($matches as $param) {
                if (isset($param['conditional']) && !empty($param['conditional'])) {
                    $patterns[$partial] = $params;
                }

                $name = preg_replace('~([/\{]{1,2})~', '', $param['name']);
                $params[] = $name;
                $expr = (isset($param['pattern']) && !empty($param['pattern'])) ? $param['pattern'] : '[^/]';
                $partial = (rtrim($partial, '/') . "/({$expr})") . ($param['conditional'] ?? '');
                $patterns[$partial] = $params;
            }
        }

        return $patterns;
    }
}
