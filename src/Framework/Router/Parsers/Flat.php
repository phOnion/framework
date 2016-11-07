<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Parsers;

use Onion\Framework\Router\Interfaces\ParserInterface;

class Flat implements ParserInterface
{
    public function parse(string $path): string
    {
        return parse_url($path, PHP_URL_PATH);
    }
}
