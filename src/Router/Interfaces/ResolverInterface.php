<?php

declare(strict_types=1);

namespace Onion\Framework\Router\Interfaces;

use Onion\Framework\Router\Interfaces\RouteInterface;

interface ResolverInterface
{
    public const PARAM_REGEX = '~(\{(?P<name>[^\:\}]+)(?:\:(?P<pattern>[^\}]+))?\}(?P<conditional>\?)?+)+~iuU';

    public function resolve(string $method, string $path): RouteInterface;
}
