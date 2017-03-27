<?php declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

class Prefix implements MatcherInterface
{
    public function match(string $pattern, string $uri): array
    {
        return (strpos($uri, $pattern) === 0) ? [] : [false];
    }
}
