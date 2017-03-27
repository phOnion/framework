<?php declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

class Regex implements MatcherInterface
{
    public function match(string $pattern, string $uri): array
    {
        $matches = null;
        if (preg_match('~^' . $pattern . '$~x', $uri, $matches)) {
            return $matches;
        }

        return [false];
    }
}
