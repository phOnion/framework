<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Stringable;
use Closure;

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
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface|UnknownDependencyException  No entry was found for this identifier.
     * @throws ContainerExceptionInterface|ContainerErrorException Error while retrieving the entry.
     * @throws \InvalidArgumentException If the provided identifier is not a string
     *
     * @return mixed Entry.
     */
    public function get(string $id): mixed
    {
        try {
            if (isset($this->invokables[$id])) {
                return $this->retrieveInvokable($id);
            } elseif (isset($this->factories[$id])) {
                return $this->retrieveFromFactory($id);
            } elseif (parent::has($id)) {
                return parent::get($id);
            } elseif ($this->getDelegate()->has($id)) {
                return $this->getDelegate()->get($id);
            }
        } catch (\RuntimeException | \InvalidArgumentException $ex) {
            throw new ContainerErrorException($ex->getMessage(), previous: $ex);
        }

        throw new UnknownDependencyException(sprintf('Unable to resolve "%s"', $id));
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has(string $id): bool
    {
        return (isset($this->invokables[$id]) || isset($this->factories[$id])) ?: parent::has($id);
    }

    /**
     * @param string $className
     * @return object
     *
     * @throws UnknownDependencyException
     */
    private function retrieveInvokable(string $className): object
    {
        $dependency = $this->invokables[$className];

        if (is_object($dependency)) {
            return $this->enforceReturnType($className, $dependency);
        }

        assert(
            is_string($dependency) && $this->has($dependency),
            new UnknownDependencyException(
                "Unable to resolve '{$dependency}'. Consider using a factory"
            )
        );

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

        if ($name instanceof Closure) {
            return $this->enforceReturnType($className, call_user_func($name, $this));
        }

        assert(
            class_exists($name),
            new \InvalidArgumentException("Provided '{$name}' does not exist")
        );

        assert(
            in_array(FactoryInterface::class, class_implements($name) ?: []) ||
                in_array(FactoryBuilderInterface::class, class_implements($name) ?: []),
            new ContainerErrorException(
                "Factory for '{$className}' does not implement any of Dependency\\Interfaces"
            )
        );

        $factory = $this->get($name);



        if ($factory instanceof FactoryBuilderInterface) {
            $factory = $factory->build($this->getDelegate(), $className);
        }

        return $this->enforceReturnType(
            $className,
            $factory->build($this->getDelegate())
        );
    }
}
