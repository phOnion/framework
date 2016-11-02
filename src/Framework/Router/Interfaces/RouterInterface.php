<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Interfaces;

use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Interfaces;
use Psr\Http\Message;

interface RouterInterface extends \Countable
{
    /**
     *
     * @api
     *
     * @param string               $method The HTTP method of the current request
     * @param Message\UriInterface $uri    Representation of the current URI
     *
     * @throws Exception\NotFoundException when no route that matches the
     * pattern is found
     * @throws Exception\NotAllowedException When the matched route does not
     * support the current request method.
     *
     * @return array
     */
    public function match(string $method, Message\UriInterface $uri): array;

    /**
     * Push a route in to the stack routes on which to perform the actual
     * matches against.
     *
     * @api
     *
     * @param array  $methods The http methods for which to register the route
     * @param string $pattern the pattern for which the route is responsible
     * @param array  $handler The of the route
     * @param string $name    Name of the current route for reverse lookup
     *
     * @return void
     */
    public function addRoute(
        string $pattern,
        DelegateInterface $handler,
        array $methods,
        string $name = null
    );
}
