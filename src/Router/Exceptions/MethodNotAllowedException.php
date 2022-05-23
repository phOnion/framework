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
    protected iterable $allowedMethods = [];

    /**
     * MethodNotAllowedException constructor.
     *
     * @param iterable $methods The methods which are supported by the current route
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(iterable $methods)
    {
        $this->allowedMethods = $methods;
    }

    /**
     * Returns the list of methods supported by the route
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return \is_array($this->allowedMethods) ? $this->allowedMethods : \iterator_to_array($this->allowedMethods);
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
