<?php declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

/**
 * Class Prefix
 *
 * @package Onion\Framework\Router\Matchers
 */
class Prefix implements MatcherInterface
{
    /**
     * @param string $pattern The parsed route pattern
     * @param string $uri Current request URI
     * @return array
     */
    public function match(string $pattern, string $uri): array
    {
        return (strpos($uri, $pattern) === 0) ? [] : [false];
    }
}
