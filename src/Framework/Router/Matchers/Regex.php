<?php declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

/**
 * Class Regex
 *
 * @package Onion\Framework\Router\Matchers
 */
class Regex implements MatcherInterface
{
    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    public function match(string $pattern, string $uri): array
    {
        $matches = null;
        if (preg_match('~^' . $pattern . '$~x', $uri, $matches)) {
            return $matches;
        }

        return [false];
    }
}
