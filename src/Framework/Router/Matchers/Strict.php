<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

class Strict implements MatcherInterface
{
    public function match(string $path, string $uri): array
    {
        return $path === $uri ? [] : [false];
    }
}
