<?php declare(strict_types=1);
namespace Onion\Framework\Router\Matchers;

use Onion\Framework\Router\Interfaces\MatcherInterface;

/**
 * Class Strict
 *
 * @package Onion\Framework\Router\Matchers
 */
class Strict implements MatcherInterface
{
    /**
     * @param string $path
     * @param string $uri
     * @return array
     */
    public function match(string $path, string $uri): array
    {
        return $path === $uri ? [] : [false];
    }
}
