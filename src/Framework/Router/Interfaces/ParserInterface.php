<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Interfaces;

/**
 * Interface ParserInterface
 * Interface to provide the skeleton structure of a parser that can be used
 * with the the `RouterInterface`.
 *
 * @see     RouterInterface
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
     *
     * @param string $path
     *
     * @return string
     */
    public function parse(string $path): string;
}
