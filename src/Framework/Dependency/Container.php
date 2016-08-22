<?php
/**
 * PHP Version 5.6.0
 *
 * @category Dependency-Injection
 * @package  Onion\Framework\Dependency
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Dependency;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Onion\Framework\Interfaces\ObjectFactoryInterface;

class Container implements ContainerInterface
{
    protected $definitions = [];

    /**
     * Container constructor. Simply assigns the contents of the $definitions
     * array that MUST have some of the keys `factories`, `invokables` and/or
     * `shared`. There represent different types of dependencies.
     *
     * `factories` - Set of objects that are called and their result is
     * returned as the dependency. (return new instance on every request)
     * `invokables` - Classes that do not have any dependencies in their
     * constructors
     * `shared` - A more of a registry, looking the same as the `invokables`,
     * but with the difference that every value entry is resolved from either
     * (return new instance on every request)
     * `factories` or `invokables` stored in the same identifier and removed
     * from them. (return same instance on every call)
     *
     * @param array|\ArrayObject $definitions List of the dependencies
     *
     * @throws \InvalidArgumentException When $definitions is not an
     * array nor instance of \ArrayObject
     */
    public function __construct($definitions)
    {
        if (!is_array($definitions) && !$definitions instanceof \ArrayObject) {
            throw new \InvalidArgumentException(
                '$definitions should be either array or instance of \ArrayObject'
            );
        }

        $this->definitions = array_merge(
            [
                'factories' => [],
                'invokables' => [],
                'shared' => []
            ],
            $definitions
        );
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @throws NotFoundException|Exception\UnknownDependency  No entry was found for
     * this identifier.
     * @throws ContainerException|Exception\ContainerErrorException Error
     * while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($key)
    {
        if (!is_string($key)) {
            throw new Exception\ContainerErrorException(
                sprintf(
                    'Dependency identifier must be a string, %s given',
                    gettype($key)
                )
            );
        }

        if ($this->has($key)) {
            $dependency = null;
            if ($this->isShared($key)) {
                $dependency = $this->retrieveShared($key);
            }

            if ($dependency === null) {
                $dependency = $this->retrieveFromFactory($key);
            }

            if ($dependency === null) {
                $dependency = $this->retrieveInvokable($key);
            }

            if ((class_exists($key) || interface_exists($key))
                && !$dependency instanceof $key
            ) {
                throw new Exception\ContainerErrorException(
                    sprintf(
                        'Resolved dependency is not instance of "%s"',
                        $key
                    )
                );
            }

            return $dependency;
        }

        throw new Exception\UnknownDependency(
            sprintf('"%s" not registered with the container', $key)
        );
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($key)
    {
        return (
            array_key_exists($key, $this->definitions['invokables']) ||
            array_key_exists($key, $this->definitions['factories']) ||
            array_key_exists($key, $this->definitions['shared'])
        );
    }

    /**
     * ,m
     *
     * @param string $identifier Dependency identifier
     *
     * @throws NotFoundException|Exception\UnknownDependency  when dependency
     * is not registered with the factories in the container.
     * @throws ContainerException|Exception\ContainerErrorException Error
     * while retrieving the entry.
     *
     * @return ObjectFactoryInterface
     */
    protected function retrieveFromFactory($identifier)
    {
        if (!array_key_exists($identifier, $this->definitions['factories'])) {
            return null;
        }

        $factory = $this->definitions['factories'][$identifier];
        if (is_string($factory)) {
            $factory = $this->retrieveInvokable($factory);
        }

        if (!$factory instanceof ObjectFactoryInterface) {
            throw new Exception\ContainerErrorException(
                sprintf(
                    'Factory for class "%s" must implement ' .
                        '\Onion\Framework\Interfaces\ObjectFactoryInterface',
                    $identifier
                )
            );
        }

        return $factory($this);
    }

    /**
     * Retrieves the dependency stored under $identifier or a
     * factory class named as $identifier by instantiating a new
     * class when called.
     *
     * @param string $identifier Dependency identifier
     *
     * @throws Exception\UnknownDependency when dependency is not registered
     * with the invokables in the container.
     * @throws ContainerException|Exception\ContainerErrorException Error
     * while retrieving the entry.
     *
     * @return mixed
     */
    protected function retrieveInvokable($identifier)
    {
        if (!array_key_exists($identifier, $this->definitions['invokables'])
            && !in_array($identifier, $this->definitions['factories'], true)
        ) {
            return null;
        }

        $object = array_key_exists($identifier, $this->definitions['invokables']) ?
            $this->definitions['invokables'][$identifier] : $identifier;

        if (!is_object($object) && !class_exists($object)) {
            throw new Exception\ContainerErrorException(
                sprintf(
                    'Class "%s" does not exist',
                    $object
                )
            );
        }

        return new $object;
    }

    /**
     * @param  string $identifier Dependency identifier
     *
     * @return object
     */
    protected function retrieveShared($identifier)
    {
        if (!is_object($this->definitions['shared'][$identifier])) {
            $referenceKey = $this->definitions['shared'][$identifier];

            $this->definitions['shared'][$identifier]
                    = $this->retrieveInvokable($referenceKey);

            if ($this->definitions['shared'][$identifier] === null) {
                $this->definitions['shared'][$identifier]
                    = $this->retrieveFromFactory($identifier);
            }
        }

        return $this->definitions['shared'][$identifier];
    }

    /**
     * Should the object identifier by $identifier be
     * shared
     *
     * @param string $identifier Dependency identifier
     *
     * @return bool
     */
    public function isShared($identifier)
    {
        return array_key_exists($identifier, $this->definitions['shared']);
    }
}
