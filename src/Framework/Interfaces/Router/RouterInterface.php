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

use Psr\Http\Message;
use Onion\Framework\Interfaces;

interface RouterInterface extends \Countable
{
    /**
     * Sets the parser that will be used to process the
     * routes (compiling & matching)
     *
     * @api
     *
     * @param ParserInterface $parser
     *
     * @return mixed
     */
    public function setParser(ParserInterface $parser);

    /**
     *
     * @api
     * @param string $method The HTTP method of the current request
     * @param Message\UriInterface $uri Representation of the current URI
     *
     * @throws Exception\NotFoundException when no route that matches the
     * pattern is found
     * @throws Exception\NotAllowedException When the matched route does not
     * support the current request method.
     *
     * @return RouteInterface
     */
    public function match($method, Message\UriInterface $uri);

    /**
     * Push a route in to the stack routes on which to perform the actual
     * matches against.
     *
     * @api
     *
     * @param array $methods The http methods for which to register the route
     * @param string $pattern the pattern for which the route is responsible
     * @param array $handler The of the route
     * @param string $name Name of the current route for reverse lookup
     *
     * @return mixed
     */
    public function addRoute(
        array $methods,
        $pattern,
        $handler,
        $name = null
    );
}
