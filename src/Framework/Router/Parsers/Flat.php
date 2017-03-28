<?php declare(strict_types=1);
namespace Onion\Framework\Router\Parsers;

use Onion\Framework\Router\Interfaces\ParserInterface;

/**
 * Class Flat
 *
 * @package Onion\Framework\Router\Parsers
 */
class Flat implements ParserInterface
{
    /**
     * @param string $path
     * @return string
     */
    public function parse(string $path): string
    {
        return parse_url($path, PHP_URL_PATH);
    }
}
