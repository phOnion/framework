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

/**
 * Class Container
 *
 * @package Onion\Framework\Dependency
 */
final class Container implements AttachableContainer
{
    /** @var string[][]|object[][] */
    private $dependencies = [];
    /** @var string[] $shared */
    private $shared = [];

    /** @var ContainerInterface|null */
    private $delegate = null;

    /**
     * Container constructor.
     *
     * @param array $dependencies
     */
    public function __construct(array $dependencies)
    {
        $this->dependencies = $dependencies;

        if (isset($this->dependencies['shared'])) {
            $this->shared = $this->dependencies['shared'] ?? [];
            unset($this->dependencies['shared']);
        }
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

        if (isset($this->dependencies[$key])) {
            return $this->dependencies[$key];
        }

        try {
            if (isset($this->dependencies['invokables'][$key])) {
                return $this->retrieveInvokable($key);
            }

            if (isset($this->dependencies['factories'][$key])) {
                return $this->retrieveFromFactory($key);
            }

            if (class_exists($key)) {
                return $this->retrieveFromReflection($key);
            }

            $key = $this->convertVariableName($key);

            if (strpos($key, '.') !== false) {
                return $this->retrieveFromDotString($key);
            }
        } catch (\RuntimeException | \InvalidArgumentException $ex) {
            throw new ContainerErrorException($ex->getMessage());
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
        $exists = (
            isset($this->dependencies[$key]) ||
            isset($this->dependencies['invokables'][$key]) ||
            isset($this->dependencies['factories'][$key]) ||
            class_exists($key)
        );

        if (!$exists) {
            $fragments = explode('.', $this->convertVariableName($key));
            $component = &$this->dependencies;
            foreach ($fragments as $fragment) {
                $exists = true;

                if (is_array($component) && isset($component[$fragment])) {
                    $component = &$component[$fragment];
                    continue;
                }

                return false;
            }
        }

        return $exists;
    }

    /**
     * @param string $className
     * @return object
     *
     * @throws UnknownDependency
     */
    private function retrieveInvokable(string $className): object
    {
        $dependency = $this->dependencies['invokables'][$className];
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
            $this->dependencies['invokables'][$className] = $result;
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
        assert(
            $parameter->hasType() || $parameter->isOptional() || $this->has($parameter->getName()),
            new ContainerErrorException(sprintf(
                'Unable to resolve a class parameter "%s" without type.',
                $parameter->getName()
            ))
        );

        try {
            $type = $parameter->hasType() ? $parameter->getType() : null;
            if ($type !== null) {
                if (!$type->isBuiltin() && $this->has((string) $type)) {
                    return $this->get((string) $parameter->getType());
                }

                if (!$parameter->isOptional()) {
                    return $this->get(strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $parameter->getName())));
                }
            }

            if ($parameter->isOptional()) {
                return $this->has($parameter->getName()) ?
                    $this->get($parameter->getName()) : $parameter->getDefaultValue();
            }
        } catch (UnknownDependency $ex) {
            throw new ContainerErrorException(sprintf(
                'Unable to find match for type: "%s (%s)". Consider using a factory',
                $parameter->getName(),
                $parameter->getType() ?? ''
            ));
        }
    }

    /**
     * @param string $className
     * @return object
     */
    private function retrieveFromFactory(string $className): object
    {
        $name = $this->dependencies['factories'][$className];
        assert(
            is_string($name),
            new ContainerErrorException(
                "Registered factory for '{$className}' must be a valid FQCN, " . gettype($className) . ' given'
            )
        );

        $container = $this->delegate ?? $this;
        $factoryReflection = new \ReflectionClass($name);

        assert(
            $factoryReflection->implementsInterface(FactoryInterface::class) ||
                $factoryReflection->implementsInterface(FactoryBuilderInterface::class),
            new ContainerErrorException(
                "Factory for '{$className}' does not implement any of Dependency\\Interfaces"
            )
        );

        $factory = $container->get($name);
        if ($factory instanceof FactoryBuilderInterface) {
            $factoryResult = $factory->build($container, $className);
        }

        if ($factory instanceof FactoryInterface) {
            $factoryResult = $factory->build($container);
        }

        if (!isset($factoryResult)) {
            throw new \RuntimeException(
                "No factory available to build {$className}"
            );
        }

        $result = $this->enforceReturnType($className, $factoryResult);
        if (in_array($className, $this->shared, true)) {
            if (!isset($this->dependencies['invokables'])) {
                $this->dependencies['invokables'] = [];
            }

            $this->dependencies['invokables'][$className] = $result;
        }

        return $result;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function retrieveFromDotString(string $name)
    {
        $fragments = explode('.', $name);
        $component = &$this->dependencies;

        foreach ($fragments as $fragment) {
            if (is_array($component) && isset($component[$fragment])) {
                $component = &$component[$fragment];
                continue;
            }

            throw new UnknownDependency(
                "Unable to resolve '{$fragment}' of '{$name}'"
            );
        }

        return $component;
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
        assert(
            $result instanceof $identifier,
            new ContainerErrorException(sprintf(
                'Unable to verify that "%s" is of type "%s"',
                get_class($result),
                $identifier
            ))
        );

        return $result;
    }

    private function isKeyValid($key): bool
    {
        return is_string($key) || is_scalar($key) ||
            (is_object($key) && method_exists($key, '__toString'));
    }
}
