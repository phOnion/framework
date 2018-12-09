<?php declare(strict_types=1);
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
    public function __construct(iterable $methods, $code = 0, \Exception $previous = null)
    {
        parent::__construct('HTTP method not allowed', $code, $previous);
        if ($methods instanceof \Traversable) {
            $methods = iterator_to_array($methods);
        }

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
