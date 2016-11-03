<?php
/**
 * PHP Version 5.6.0
 *
 * @category Routing
 * @package  Onion\Framework\Interfaces\Router
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces\Router;

/**
 * Interface ParserInterface
 * Interface to provide the skeleton structure of a parser that can be used
 * with the the `RouterInterface`.
 *
 * @see RouterInterface
 *
 * @package Onion\Framework\Interfaces\Router
 */
interface ParserInterface
{
    /**
     * Performs any conversion(if necessary) on the provided $path argument
     * to enable later checking(matching) against a provided path. Example of
     * such activity is when transforming(tokenizing) a simple pseudo-regex
     * pattern to a valid regex that can later be used with `preg_match` to
     * determine if there is a match against a request path.
     *
     * @api
     * @param string $path
     *
     * @return array[array, string[]]
     */
    public function parse($path);

    /**
     * Attempts to match the $path against the $pattern and if it matches
     * return a list of the parameters that are found, if any, or an empty
     * array. Under no circumstances it must not return anything other than
     * an array, nor on with numeric indexes.
     *
     * @api
     * @param string $pattern
     * @param string $path
     *
     *
     * @return array|false List of the named parameters of the route. (Should not
     * return any integer indexes within said array, or false if it is not a match
     */
    public function match($pattern, $path);
}
