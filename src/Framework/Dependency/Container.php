<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

/**
 * Class Container
 *
 * @package Onion\Framework\Dependency
 */
final class Container implements ContainerInterface
{
    private $dependencies = [];
    private $invokables = [];
    private $factories = [];
    private $shared = [];

    /**
     * Container constructor.
     *
     * @param array $dependencies
     */
    public function __construct(array $dependencies)
    {
        $this->dependencies = $dependencies;
        if (isset($this->dependencies['invokables'])) {
            $this->invokables = $this->dependencies['invokables'];
            unset($this->dependencies['invokables']);
        }

        if (isset($this->dependencies['factories'])) {
            $this->factories = $this->dependencies['factories'];
            unset($this->dependencies['factories']);
        }

        if (isset($this->dependencies['shared'])) {
            $this->shared = $this->dependencies['shared'] ?? [];
            unset($this->dependencies['shared']);
        }

        $this->dependencies = array_merge($this->dependencies, $dependencies);
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
        assert(is_string($key), new \InvalidArgumentException(sprintf(
            'Dependency identifier must be a string, "%s" given',
            gettype($key)
        )));

        if (array_key_exists($key, $this->dependencies)) {
            return $this->dependencies[$key];
        }

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

            if (is_string($key)) {
                if (strpos($this->convertVariableName($key), '.') !== false) {
                    $key=$this->convertVariableName($key);

                    return $this->retrieveFromDotString($key);
                }
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
        $exists = (
            array_key_exists($key, $this->dependencies) ||
            isset($this->dependencies['invokables'][$key]) ||
            isset($this->dependencies['factories'][$key]) ||
            class_exists($key)
        );

        if (!$exists) {
            if (strpos(($namePath = $this->convertVariableName($key)), '.') !== false) {
                $keys = explode('.', $namePath);
                $ref = &$this->dependencies;
                while ($keys !== []) {
                    $component = array_shift($keys);
                    if (isset($ref[$component])) {
                        $exists=true;
                        $ref= &$ref[$component];
                        continue;
                    }

                    $exists = false;
                    break;
                }
            }
        }

        return $exists;
    }

    /**
     * @param string $className
     * @return mixed
     * @throws \RuntimeException
     * @throws UnknownDependency
     */
    private function retrieveInvokable(string $className)
    {
        $dependency = $this->invokables[$className];
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

        return $this->enforceReturnType($className, $result ?? $this->retrieveFromReflection($dependency));
    }

    /**
     * @param string $className
     * @return mixed|object
     * @throws ContainerErrorException
     */
    private function retrieveFromReflection(string $className)
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->getConstructor() === null) {
            return $classReflection->newInstanceWithoutConstructor();
        }

        if ($classReflection->getConstructor() !== null && $classReflection->getConstructor()->getParameters() === []) {
            return $classReflection->newInstance();
        }

        $constructorRef = $classReflection->getConstructor();
        $parameters = [];
        foreach ($constructorRef->getParameters() as $parameter) {
            if (!$parameter->hasType() && !$parameter->isOptional() && !$this->has($parameter->getName())) {
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

            if ($parameter->getType() !== null && $parameter->getType()->isBuiltin() && !$parameter->isOptional()) {
                $parameters[$parameter->getPosition()] = $this->get($parameter->getName());
                continue;
            }

            if ($parameter->isOptional()) {
                $parameters[$parameter->getPosition()] = $this->has($parameter->getName()) ?
                    $this->get($parameter->getName()) : $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerErrorException(sprintf(
                'Unable to find match for type: "%s". Consider using a factory',
                $parameter->getType()
            ));
        }

        return $this->enforceReturnType($className, $classReflection->newInstance(...$parameters));
    }

    /**
     * @param string $className
     * @return mixed
     */
    private function retrieveFromFactory(string $className)
    {
        $factory = $this->factories[$className];
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
        $result = $this->enforceReturnType($className, $factory->build($this));
        if (in_array($className, $this->shared, true)) {
            $this->invokables[$className] = $result;
            unset($this->factories[$className]);
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
        $lead = array_shift($fragments);
        $stack = "$lead";

        assert(
            isset($this->dependencies[$lead]),
            new ContainerErrorException(
                "No definition available for '{$stack}' inside container"
            )
        );

        $component = $this->dependencies[$lead];

        foreach ($fragments as $fragment) {
            $stack .= ".$fragment";
            assert(
                (
                    is_array($component) || $component instanceof \ArrayAccess || $component instanceof \ArrayObject
                ) && isset($component[$fragment]),
                new ContainerErrorException(
                    "No definition available for '{$stack}' inside container"
                )
            );

            $component = $component[$fragment];
        }

        return $component;
    }

    /**
     * @param string $name
     * @return string
     */
    private function convertVariableName(string $name): string
    {
        return str_replace('_', '.', strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)));
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
        if (is_string($identifier)) {
            if (class_exists($identifier) || interface_exists($identifier) ||
                (function_exists("is_$identifier"))
            ) {
                assert(
                    $result instanceof $identifier ||
                    (function_exists("is_$identifier") && call_user_func("is_$identifier", $result)),
                    new ContainerErrorException(sprintf(
                        'Unable to verify that "%s" is of type "%s"',
                        is_object($result) ? get_class($result) : $result,
                        $identifier
                    ))
                );
            }
        }

        return $result;
    }
}
