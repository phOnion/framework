<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionType;

/**
 * Class Container
 *
 * @package Onion\Framework\Dependency
 */
final class Container implements AttachableContainer
{
    /** @var string[]|object[] $invokables */
    public $invokables = [];
    /** @var string[] $factories */
    public $factories = [];

    /** @var string[] $shared */
    private $shared = [];

    /** @var ContainerInterface */
    private $delegate;

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
     * @codeCoverageIgnore
     */
    public function attach(ContainerInterface $container): void
    {
        $this->delegate = $container;
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

            if (class_exists($key)) {
                return $this->retrieveFromReflection($key);
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
        return (
            isset($this->invokables[$key]) ||
            isset($this->factories[$key]) ||
            class_exists($key)
        );
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
                "Unable to resolve '$dependency'. Consider using a factory"
            );
        }

        $result = $this->retrieveFromReflection($dependency);
        if (in_array($className, $this->shared, true)) {
            $this->invokables[$className] = $result;
        }

        return $this->enforceReturnType($className, $result);
    }

    /**
     * @param string $className
     * @return object
     * @throws ContainerErrorException
     */
    private function retrieveFromReflection(string $className): object
    {
        if (!class_exists($className)) {
            throw new UnknownDependency("Provided '{$className}' does not exist");
        }
        $classReflection = new \ReflectionClass($className);
        $constructorRef = $classReflection->getConstructor();

        if ($constructorRef === null) {
            return $classReflection->newInstanceWithoutConstructor();
        }

        $parameters = [];
        foreach ($constructorRef->getParameters() as $parameter) {
            $parameters[$parameter->getPosition()] = $this->resolveReflectionParameter($parameter);
        }

        return $this->enforceReturnType($className, $classReflection->newInstance(...$parameters));
    }

    /**
     * @return mixed
     */
    private function resolveReflectionParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->hasType() ? $this->formatType($parameter->getType()) : null;
        try {
            if (is_string($type)) {
                $typeKey = trim($type, '?');
                if ($this->has($typeKey)) {
                    return $this->get($typeKey);
                }

                if (!$parameter->isOptional()) {
                    return $this->get($this->convertVariableName($parameter->getName()));
                }
            }

            if ($parameter->isOptional()) {
                return $this->delegate->has($parameter->getName()) ?
                    $this->delegate->get($parameter->getName()) : $parameter->getDefaultValue();
            }
        } catch (UnknownDependency $ex) {
            throw new ContainerErrorException(sprintf(
                'Unable to find match for type: "%s (%s)". Consider using a factory',
                $parameter->getName(),
                $type
            ), (int) $ex->getCode(), $ex);
        }

        throw new ContainerErrorException(sprintf(
            'Unable to resolve a class parameter "%s" without type.',
            $parameter->getName()
        ));
    }

    /**
     * @param string $className
     * @return object
     */
    private function retrieveFromFactory(string $className): object
    {
        $name = $this->factories[$className];
        assert(
            is_string($name),
            new ContainerErrorException(
                "Registered factory for '{$className}' must be a valid FQCN, " . gettype($className) . ' given'
            )
        );

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

    /**
     * @param string $name
     * @return string
     */
    private function convertVariableName(string $name): string
    {
        return str_replace('\\', '', strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $name)));
    }

    /**
     * Helper to verify that the result is instance of
     * the identifier (if it is a class/interface)
     *
     * @param string $identifier
     * @param object  $result
     *
     * @return object
     * @throws ContainerErrorException
     */
    private function enforceReturnType(string $identifier, object $result): object
    {
        if (interface_exists($identifier) || class_exists($identifier)) {
            assert(
                $result instanceof $identifier,
                new ContainerErrorException(sprintf(
                    'Unable to verify that "%s" is of type "%s"',
                    get_class($result),
                    $identifier
                ))
            );
        }

        return $result;
    }

    private function isKeyValid($key): bool
    {
        return is_string($key) || is_scalar($key) ||
            (is_object($key) && method_exists($key, '__toString'));
    }

    private function formatType(?ReflectionType $type): string
    {
        if ($type === null) {
            return 'any';
        }

        return $type->allowsNull() ? "?{$type}" : (string) $type;
    }
}
