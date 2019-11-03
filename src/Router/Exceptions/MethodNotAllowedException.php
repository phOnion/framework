<?php

declare(strict_types=1);

namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;

/**
 * Class MethodNotAllowedException
 *
 * @package Onion\Framework\Router\Exceptions
 */
class MethodNotAllowedException extends \Exception implements NotAllowedException
{
    /** @var iterable $allowedMethods */
    protected $allowedMethods = [];

    /**
     * MethodNotAllowedException constructor.
     *
     * @param iterable $methods The methods which are supported by the current route
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(iterable $methods)
    {
        $this->setAllowedMethods($methods);
    }

    /**
     * Returns the list of methods supported by the route
     *
     * @return iterable
     */
    public function getAllowedMethods(): iterable
    {
        return $this->allowedMethods;
    }

    /**
     * Sets the methods that ARE supported by the method
     *
     * @param iterable $methods
     *
     * @return void
     */
    public function setAllowedMethods(iterable $methods): void
    {
        $this->allowedMethods = $methods;
    }
}
