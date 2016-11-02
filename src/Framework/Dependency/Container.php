<?php
declare(strict_types = 1);
namespace Onion\Framework\Dependency;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class Container implements ContainerInterface
{
    private $dependencies = [];

    public function __construct(array $dependencies)
    {
        $this->dependencies = [
            'invokables' => [],
            'factories' => [],
            'shared' => []
        ];
        $this->dependencies = array_merge($this->dependencies, $dependencies);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @throws NotFoundException|UnknownDependency  No entry was found for this identifier.
     * @throws ContainerException|ContainerErrorException Error while retrieving the entry.
     * @throws \InvalidArgumentException If the provided identifier is not a string
     *
     * @return mixed Entry.
     */
    public function get($key)
    {
        assert(is_string($key), new \InvalidArgumentException(sprintf(
            'Dependency identifier must be a string, "%s" given',
            gettype($key)
        )));

        if (array_key_exists($key, $this->dependencies)) {
            return $this->dependencies[$key];
        }

        try {
            if (array_key_exists($key, $this->dependencies['invokables'])) {
                return $this->retrieveInvokable($key);
            }

            if (array_key_exists($key, $this->dependencies['factories'])) {
                return $this->retrieveFromFactory($key);
            }

            if (class_exists($key)) {
                return $this->retrieveFromReflection($key);
            }
        } catch (\RuntimeException $ex) {
            throw new ContainerErrorException($ex->getMessage(), 0, $ex);
        }

        throw new UnknownDependency(sprintf('Unable to resolve "%s"', $key));
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($key): bool
    {
        return (
            array_key_exists($key, $this->dependencies) ||
            array_key_exists($key, $this->dependencies['invokables']) ||
            array_key_exists($key, $this->dependencies['factories']) ||
            class_exists($key)
        );
    }

    private function retrieveInvokable(string $className)
    {
        $dependency = $this->dependencies['invokables'][$className];
        if (is_object($dependency)) {
            return $dependency;
        }

        if (!is_string($dependency)) {
            throw new \RuntimeException(
                "Invalid invokable definition encountered while resolving '$className'. '" .
                'Expected a string, but received ' . gettype($dependency)
            );
        }

        if (!$this->has($dependency)) {
            throw new UnknownDependency(
                "Unable to resolve '$dependency'. Consider using a factory"
            );
        }

        return $this->enforceReturnType($className, $this->retrieveFromReflection($dependency));
    }

    /**
     * Helper to verify that the result is instance of
     * the identifier (if it is a class/interface)
     *
     * @param string $identifier
     * @param mixed  $result
     *
     * @return mixed
     * @throws ContainerErrorException
     */
    private function enforceReturnType($identifier, $result)
    {
        if (class_exists($identifier) || interface_exists($identifier)) {
            assert(
                $result instanceof $identifier,
                new ContainerErrorException(sprintf(
                    'Unable to verify that "%s" is instance of "%s"',
                    get_class($result),
                    $identifier
                ))
            );
        }

        return $result;
    }

    public function retrieveFromReflection(string $className)
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->getConstructor() === null || $classReflection->getConstructor()->getParameters() === []) {
            return $classReflection->newInstanceWithoutConstructor();
        }
        $constructorRef = $classReflection->getConstructor();
        $parameters = [];
        foreach ($constructorRef->getParameters() as $parameter) {
            if (!$parameter->hasType() && !$parameter->isOptional()) {
                throw new ContainerErrorException(sprintf(
                    'Unable to resolve a class parameter "%s" of "%s::%s" without type ',
                    $parameter->getName(),
                    $classReflection->getName(),
                    $constructorRef->getName()
                ));
            }


            if ($this->has((string)$parameter->getType())) {
                $parameters[$parameter->getPosition()] = $this->get((string)$parameter->getType());
                continue;
            }

            if ($parameter->isOptional()) {
                $parameters[$parameter->getPosition()] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerErrorException(sprintf(
                'Unable to find match for type: "%s". Consider using a factory',
                $parameter->getType()
            ));
        }

        return $this->enforceReturnType($className, $classReflection->newInstance(...$parameters));
    }

    private function retrieveFromFactory(string $className)
    {
        $factory = $this->dependencies['factories'][$className];
        if (!is_object($factory)) {
            $factory = new $factory();
        }

        assert(
            $factory instanceof FactoryInterface,
            new ContainerErrorException(
                "Factory for '$className' does not implement Dependency\\Interfaces\\FactoryInterface"
            )
        );

        /**
         * @var $factory FactoryInterface
         */
        $result = $factory->build($this);
        if (in_array($className, $this->dependencies['shared'], true)) {
            $this->dependencies['invokables'][$className] = $result;
            unset($this->dependencies['factories'][$className]);
        }

        return $this->enforceReturnType($className, $result);
    }

}
