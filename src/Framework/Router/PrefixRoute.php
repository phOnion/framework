<?php declare(strict_types=1);
namespace Onion\Framework\Router;

class PrefixRoute extends RegexRoute
{
    public function __construct(string $pattern, string $name = null)
    {
        $pattern = rtrim($pattern, '/');
        parent::__construct("{$pattern}/*", $name);
    }
}
