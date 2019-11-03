<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Common\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Common\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Container
 *
 * @package Onion\Framework\Dependency
 */
final class Container extends ReflectionContainer implements AttachableContainer, ContainerInterface
{
    use ContainerTrait;
    use AttachableContainerTrait;

    /** @var string[]|object[] $invokables */
    private $invokables = [];

    /** @var string[] $factories */
    private $factories = [];

    /** @var string[] $shared */
    private $shared = [];

    /**
     * Container constructor.
     *
     * @param array $dependencies
     */
    public function __construct(array $dependencies)
    {
        $this->invokables = $dependencies['invokables'] ?? [];
        $this->factories = $dependencies['factories'] ?? [];
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface|UnknownDependency  No entry was found for this identifier.
     * @throws ContainerExceptionInterface|ContainerErrorException Error while retrieving the entry.
     * @throws \InvalidArgumentException If the provided identifier is not a string
     *
     * @return mixed Entry.
     */
    public function get($key)
    {
        assert(
            $this->isKeyValid($key),
            new \InvalidArgumentException(sprintf(
                'Provided key must be a string, %s given',
                gettype($key)
            ))
        );

        try {
            if (isset($this->invokables[$key])) {
                return $this->retrieveInvokable($key);
            }

            if (isset($this->factories[$key])) {
                return $this->retrieveFromFactory($key);
            }

            if (parent::has($key)) {
                return parent::get($key);
            }

            if ($this->getDelegate()->has($key)) {
                return $this->getDelegate()->get($key);
            }
        } catch (\RuntimeException | \InvalidArgumentException $ex) {
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
        if (!$this->isKeyValid($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Provided key must be a string, %s given',
                gettype($key)
            ));
        }

        return (isset($this->invokables[$key]) || isset($this->factories[$key])) ?: parent::has($key);
    }

    /**
     * @param string $className
     * @return object
     *
     * @throws UnknownDependency
     */
    private function retrieveInvokable(string $className): object
    {
        $dependency = $this->invokables[$className];
        if (is_object($dependency)) {
            return $this->enforceReturnType($className, $dependency);
        }

        if (!$this->has($dependency)) {
            throw new UnknownDependency(
                "Unable to resolve '{$dependency}'. Consider using a factory"
            );
        }

        return $this->enforceReturnType($className, parent::get($dependency));
    }

    /**
     * @param string $className
     * @return object
     */
    private function retrieveFromFactory(string $className): object
    {
        $name = $this->factories[$className];
        assert(
            is_string($name) || is_callable($name),
            new ContainerErrorException(
                "Registered factory for '{$className}' must be a valid FQCN, " . gettype($name) . ' given'
            )
        );

        if (is_callable($name)) {
            return $this->enforceReturnType($className, call_user_func($name, $this));
        }

        assert(
            class_exists($name),
            new \InvalidArgumentException("Provided '{$name}' does not exist")
        );

        $factoryReflection = new \ReflectionClass($name);

        assert(
            $factoryReflection->implementsInterface(FactoryInterface::class) ||
                $factoryReflection->implementsInterface(FactoryBuilderInterface::class),
            new ContainerErrorException(
                "Factory for '{$className}' does not implement any of Dependency\\Interfaces"
            )
        );

        $factory = $this->get($name);
        if ($factory instanceof FactoryBuilderInterface) {
            $factory = $factory->build($this->getDelegate(), $className);
        }

        if ($factory instanceof FactoryInterface) {
            $factoryResult = $factory->build($this->getDelegate());
        }

        if (!isset($factoryResult)) {
            throw new \RuntimeException(
                "No factory available to build {$className}"
            );
        }

        return $this->enforceReturnType($className, $factoryResult);
    }
}
