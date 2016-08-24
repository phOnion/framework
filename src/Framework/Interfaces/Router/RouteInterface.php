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

use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;

/**
 * This interface is intended to define the skeleton in
 * a more uniformed representation of every route
 * definition.
 *
 * The parts of a route object that *MUST* be present in
 * order to consider the route valid are the `pattern` and
 * the `handler`, everything else should be considered
 * optional and as such should not fail the current call
 * to the application if not present/populated.
 *
 * @package Onion\Framework\Interfaces\Router
 */
interface RouteInterface extends \Serializable, \JsonSerializable
{
    /**
     * Defines the methods which are supported by the current
     * route object. Each entry in the `$methods` parameters
     * must be a valid "method", that the handler associated
     * is capable of understanding/handling.
     *
     * @param array $methods List of strings each identifying a
     * method (uppercase)
     * @return RouteInterface
     */
    public function setSupportedMethods(array $methods = []);

    /**
     * Sets the name of the current route. This is
     * particularly useful when doing reverse lookup,
     * when only the name of the route is available.
     *
     *
     * @param string $name The name
     * @return RouteInterface
     */
    public function setName($name);

    /**
     * Sets the callable, which will be doing the main
     * route logic (think controller). It is recommended
     * to use objects instead of closures, although they
     * will work as well, it WILL cause problems when in
     * scenarios such as caching the already parsed
     * router.
     *
     * @see MiddlewareInterface Minimal interface, that ensures compatibility
     *          with majority of PSR-7 implementations.
     *
     * @param array MiddlewareInterface[] The handler to dispatch
     *              for the request
     * @return RouteInterface
     */
    public function setMiddleware(array $callable);

    /**
     * The pattern which this route is responsible for.
     * Think about the path of a HTTP request, most of
     * the time there will be a route responsible for
     * the `/` path. the pattern responsible for handling
     * this route must match only that route and not act as
     * a catch-all route, unless the it's very definition is
     * intended to behave that way (equivalent to the
     * following regular expression `/^\/$/`)
     *
     * @param $pattern
     * @return RouteInterface
     */
    public function setPattern($pattern);

    /**
     * Once the route is matched, this method should be used to
     * inject all of the route parameters, if any. This is
     * provides a consistent way of the router to set the matches
     * and allow the application dispatcher to add those parameters
     * to the PSR-7 ServerRequestInterface object.
     *
     * @param array $params The parameters of the current request
     * @return RouteInterface
     */
    public function setParams(array $params);

    /**
     * Retrieves the route name or an empty string ('') if no name
     * has been assigned to this particular route.
     *
     * @return string
     */
    public function getName();

    /**
     * Retrieves the handler of the route.
     * A route must always have a valid handler and/or
     * a set of handlers defined, which the router should
     * be capable of handling. In rare cases the router is
     * allowed to not implement the required functionality
     * and as an exception case, *only the last* entry
     * *MUST* be used as a handler.
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddleware();

    /**
     * The pattern of the current route, to be used by the
     * `ParserInterface` implementation when attempting to
     * match a the current request route to the result of
     * this method call.
     *
     * @see ParserInterface
     *
     * @return string
     */
    public function getPattern();

    /**
     * The list of the parameters of the request or
     * empty array if for some reason the `ParserInterface`
     * implementation does not support this kind of
     * functionality, which is the case with
     * `Router\Parsers\Flat`
     *
     * @see \Onion\Framework\Router\Parsers\Flat Direct comparison parser,
     * the most minimalistic approach that uses `$path1 === $path2` approach
     * when attempting to match the route.
     * @see ParserInterface Interface that defines the skeleton structure of
     *          all parsers which can be used with `RouterInterface`
     *
     * @return array
     */
    public function getParams();

    /**
     * @return array List of methods supported by the route
     */
    public function getSupportedMethods();
}
