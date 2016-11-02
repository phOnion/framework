<?php
declare(strict_types = 1);
namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;

class MethodNotAllowedException extends \Exception implements NotAllowedException
{
    protected $allowedMethods = [];

    public function __construct(array $methods, $code = 0, \Exception $previous = null)
    {
        parent::__construct('HTTP method not allowed', $code, $previous);
        $this->setAllowedMethods($methods);
    }

    /**
     * Returns the list of methods supported by the route
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * Sets the methods that ARE supported by the method
     *
     * @param array $methods
     *
     * @return void
     */
    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = $methods;
    }
}
