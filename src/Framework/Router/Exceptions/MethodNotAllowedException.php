<?php
/**
 * PHP Version 5.6.0
 *
 * @category Errors
 * @package  Onion\Framework\Router\Exceptions
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Interfaces\Router\Exception\NotAllowedException;

class MethodNotAllowedException extends \Exception implements NotAllowedException
{
    protected $allowedMethods = [];
    public function __construct(array $methods, $code = 0, \Exception $previous = null)
    {
        parent::__construct('HTTP method not allowed', $code, $previous);
        $this->setAllowedMethods($methods);
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

    /**
     * Returns the list of methods supported by the route
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
