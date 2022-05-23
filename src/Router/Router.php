<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Onion\Framework\Router\Interfaces\{CollectorInterface, RouterInterface};
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\RequestInterface;

class Router implements RouterInterface
{
    public function __construct(
        private readonly CollectorInterface $collector,
    ) {
    }

    public function match(RequestInterface $request): RouteInterface
    {
        foreach ($this->collector as $pattern => $data) {

            if (!\preg_match("~^(?|{$pattern})$~J", $request->getUri()->getPath(), $matches, PREG_UNMATCHED_AS_NULL)) {
                continue;
            }
            /** @var RouteInterface $route */
            $route = $data[$matches['MARK']];

            if (!$route->hasMethod($request->getMethod())) {
                throw new MethodNotAllowedException($route->getMethods());
            }

            return $route->withParameters(\array_filter(
                $matches,
                fn ($key) => !\is_int($key) && $key !== 'MARK',
                ARRAY_FILTER_USE_KEY
            ));
        }

        throw new NotFoundException();
    }
}
