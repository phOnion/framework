<?php
declare(strict_types = 1);
namespace Onion\Framework\Router\Interfaces\Exception;

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
     *
     * @return void
     */
    public function setAllowedMethods(array $methods);

    /**
     * Returns the list of methods supported by the route
     *
     * @return array
     */
    public function getAllowedMethods(): array;
}
