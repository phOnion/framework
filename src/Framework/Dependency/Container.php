<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
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
    private $dependencies = [];
    private $shared = [];

    /** @var ContainerInterface */
    private $delegate = null;

    /**
     * Container constructor.
     *
     * @param object $dependencies
     */
    public function __construct(object $dependencies)
    {
        $this->dependencies = $dependencies;

        if (isset($this->dependencies->shared)) {
            $this->shared = $this->dependencies->shared ?? [];
            unset($this->dependencies->shared);
        }
    }

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
        $key = (string) $key;

        if (isset($this->dependencies->$key)) {
            return $this->dependencies->$key;
        }

        try {
            if (isset($this->dependencies->invokables->$key)) {
                return $this->retrieveInvokable($key);
            }

            if (isset($this->dependencies->factories->$key)) {
                return $this->retrieveFromFactory($key);
            }

            if (class_exists($key)) {
                return $this->retrieveFromReflection($key);
            }

            if (is_string($key)) {
                $key = $this->convertVariableName($key);

                if (strpos($key, '.') !== false) {
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
        $key = (string) $key;
        $exists = (
            isset($this->dependencies->$key) ||
            isset($this->dependencies->invokables->$key) ||
            isset($this->dependencies->factories->$key) ||
            class_exists($key)
        );

        if (!$exists) {
            $namePath = $this->convertVariableName($key);
            if (strpos($namePath, '.') !== false) {
                $keys = explode('.', $namePath);
                $ref = &$this->dependencies;
                while ($keys !== []) {
                    $component = array_shift($keys);
                    if (isset($ref->$component)) {
                        $exists=true;
                        $ref= &$ref->$component;
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
    private function retrieveInvokable(string $className): object
    {
        $dependency = $this->dependencies->invokables->$className;
        if (is_object($dependency)) {
            return $this->enforceReturnType($className, $dependency);
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

        $result = $this->retrieveFromReflection($dependency);
        if (in_array($className, $this->shared, true)) {
            if (!isset($this->dependencies->invokables)) {
                $this->dependencies->invokables = new \stdClass;
            }

            $this->dependencies->invokables->{$className} = $result;
        }

        return $this->enforceReturnType($className, $result);
    }

    /**
     * @param string $className
     * @return mixed|object
     * @throws ContainerErrorException
     */
    private function retrieveFromReflection(string $className): object
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->getConstructor() === null) {
            return $classReflection->newInstanceWithoutConstructor();
        }

        if ($classReflection->getConstructor()->getParameters() === []) {
            return $classReflection->newInstance();
        }

        $constructorRef = $classReflection->getConstructor();
        $parameters = [];
        foreach ($constructorRef->getParameters() as $parameter) {
            assert(
                $parameter->hasType() || $parameter->isOptional() || $this->has($parameter->getName()),
                new ContainerErrorException(sprintf(
                    'Unable to resolve a class parameter "%s" of "%s::%s" without type ',
                    $parameter->getName(),
                    $classReflection->getName(),
                    $constructorRef->getName()
                ))
            );

            if ($parameter->hasType()) {
                if (!$parameter->getType()->isBuiltin() && $this->has($parameter->getType())) {
                    $parameters[$parameter->getPosition()] = $this->get($parameter->getType());
                    continue;
                }

                if (!$parameter->isOptional()) {
                    $parameters[$parameter->getPosition()] = $this->get($parameter->getName());
                    continue;
                }
            }

            if ($parameter->isOptional()) {
                $parameters[$parameter->getPosition()] = $this->has($parameter->getName()) ?
                    $this->get($parameter->getName()) : $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerErrorException(sprintf(
                'Unable to find match for type: "%s". Consider using a factory',
                $parameter->getType() ?? $parameter->getName()
            ));
        }

        return $this->enforceReturnType($className, $classReflection->newInstance(...$parameters));
    }

    /**
     * @param string $className
     * @return mixed
     */
    private function retrieveFromFactory(string $className): object
    {
        $name = $this->dependencies->factories->$className;
        assert(
            is_string($name),
            new ContainerErrorException(
                "Registered factory for '{$className}' must be a valid FQCN, " . gettype($className) . ' given'
            )
        );

        $factory = (new \ReflectionClass($name))
            ->newInstance();

        assert(
            $factory instanceof FactoryInterface,
            new ContainerErrorException(
                "Factory for '{$className}' does not implement Dependency\\Interfaces\\FactoryInterface"
            )
        );

        /**
         * @var $factory FactoryInterface
         */
        $result = $this->enforceReturnType($className, $factory->build($this->delegate ?? $this));
        if (in_array($className, $this->shared, true)) {
            if (!isset($this->dependencies->invokables)) {
                $this->dependencies->invokables = new \stdClass;
            }

            $this->dependencies->invokables->{$className} = $result;
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
            isset($this->dependencies->$lead),
            new ContainerErrorException(
                "No definition available for '{$stack}' inside container"
            )
        );

        $component = $this->dependencies->$lead;

        foreach ($fragments as $fragment) {
            $stack .= ".$fragment";
            assert(
                isset($component->$fragment),
                new ContainerErrorException(
                    "No definition available for '{$stack}' inside container"
                )
            );

            $component = $component->$fragment;
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
     * @param mixed  $result
     *
     * @return mixed
     * @throws ContainerErrorException
     */
    private function enforceReturnType(string $identifier, object $result): object
    {
        if (is_string($identifier)) {
            if (class_exists($identifier) || interface_exists($identifier) ||
                (function_exists("is_{$identifier}"))
            ) {
                assert(
                    $result instanceof $identifier ||
                    (function_exists("is_{$identifier}") && call_user_func("is_{$identifier}", $result)),
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
