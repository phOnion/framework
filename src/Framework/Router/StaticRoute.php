<?php
declare(strict_types=1);
namespace Onion\Framework\Router;

use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Http\Middleware\RequestHandler;

class StaticRoute extends Route
{
    public function isMatch(string $path): bool
    {
        return $this->getPattern() === $path;
    }
}
