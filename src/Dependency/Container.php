<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Dependency\Traits\ContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Onion\Framework\Dependency\Traits\AttachableContainerTrait;

/**
 * Class Container
 *
 * @package Onion\Framework\Dependency
 */
final class Container implements AttachableContainer, ContainerInterface
{
    /** @var string[]|object[] $invokables */
    private $invokables = [];

    /** @var string[] $factories */
    private $factories = [];

    /** @var string[] $shared */
    private $shared = [];

    /** @var ContainerInterface */
    private $delegate;

    use ContainerTrait, AttachableContainerTrait;

    /**
     * Container constructor.
     *
     * @param array $dependencies
     */
    public function __construct(array $dependencies)
    {
        $this->invokables = $dependencies['invokables'] ?? [];
        $this->factories = $dependencies['factories'] ?? [];

        if (isset($dependencies['shared'])) {
            $this->shared = $dependencies['shared'] ?? [];
        }

        $this->delegate = $this;
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
        if (!$this->isKeyValid($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Provided key must be a string, %s given',
                gettype($key)
            ));
        }

        $key = (string) $key;
        try {
            if (isset($this->invokables[$key])) {
                return $this->retrieveInvokable($key);
            }

            if (isset($this->factories[$key])) {
                return $this->retrieveFromFactory($key);
            }

            if ($this->delegate->has($key)) {
                return $this->delegate->get($key);
            }
        } catch (\RuntimeException | \InvalidArgumentException $ex) {
            throw new ContainerErrorException($ex->getMessage(), (int) $ex->getCode(), $ex);
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

        $key = (string) $key;
        return (isset($this->invokables[$key]) || isset($this->factories[$key]));
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

        $result = $this->delegate->get($dependency);
        if (in_array($className, $this->shared, true)) {
            $this->invokables[$className] = $result;
        }

        return $this->enforceReturnType($className, $result);
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
            $result = $this->enforceReturnType($className, call_user_func($name, $this));
            if (in_array($className, $this->shared, true)) {
                $this->invokables[$className] = $result;
            }

            return $result;
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

        $factory = $this->delegate->get($name);
        if ($factory instanceof FactoryBuilderInterface) {
            $factory = $factory->build($this->delegate, $className);
        }

        if ($factory instanceof FactoryInterface) {
            $factoryResult = $factory->build($this->delegate);
        }

        if (!isset($factoryResult)) {
            throw new \RuntimeException(
                "No factory available to build {$className}"
            );
        }

        $result = $this->enforceReturnType($className, $factoryResult);
        if (in_array($className, $this->shared, true)) {
            $this->invokables[$className] = $result;
        }

        return $result;
    }
}
