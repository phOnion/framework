<?php
/**
 * PHP Version 5.6.0
 *
 * @category Errors
 * @package  Onion\Framework\Interfaces\Router\Exception
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Interfaces\Router\Exception;

/**
 * Class NotAllowedException
 * Exception to indicate that a route with pattern that matches the requested
 * one exists, but does not indicate that it supports the currently requested
 * method.
 *
 * @package Onion\Framework\Interfaces\Router\Exception
 */
interface NotAllowedException
{
    /**
     * Sets the methods that ARE supported by the method
     *
     * @param array $methods
     * @return void
     */
    public function setAllowedMethods(array $methods);

    /**
     * Returns the list of methods supported by the route
     *
     * @return array
     */
    public function getAllowedMethods();
}
