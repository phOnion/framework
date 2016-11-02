<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Interfaces;

interface MatcherInterface
{
    /**
     * Attempts to match the $path against the $pattern and if it matches
     * return a list of the parameters that are found, if any, or an empty
     * array. Under no circumstances it must not return anything other than
     * an array, nor on with numeric indexes.
     *
     * @api
     *
     * @param string $pattern
     * @param string $path
     *
     *
     * @return array List of the named parameters of the route. (Should not
     * return any integer indexes within said array, or false if it is not a match
     */
    public function match(string $pattern, string $path): array;
}
