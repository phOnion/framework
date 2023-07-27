<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Onion\Framework\Router\Interfaces\ParserInterface;

class Parser implements ParserInterface
{
    private const WILDCARD_PARAM = '~{(?P<name>[[:alpha:]][^:{}]+)}~i';
    private const REQUIRED_PARAM = "~{(?P<name>[^:{}]+)\:(?P<pattern>[^/]+|[^}]+)}~i";
    private const OPTIONAL_PARAM = "~(?P<prefix>/)?{(?P<name>[^:{}]+)\:(?P<pattern>[^/]+|[^}]+)}(?P<conditional>\?)?~i";

    public function parse(string $pattern): string
    {
        if (\preg_match('/{/', $pattern)) {
            $pattern = \preg_replace(static::WILDCARD_PARAM, '{$1:[^/]+}', $pattern);

            if (!\preg_match('/}\?/', $pattern)) {
                $pattern = \preg_replace(static::REQUIRED_PARAM, '(?P<$1>$2)', $pattern);
            } else {
                $pattern = \preg_replace(static::OPTIONAL_PARAM, '(?:$1(?P<$2>$3))$4', $pattern);
            }
        }

        return $pattern;
    }
}
