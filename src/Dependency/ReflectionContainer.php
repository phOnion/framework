<?php
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Dependency\Traits\ContainerTrait;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class ReflectionContainer implements ContainerInterface, AttachableContainer
{
    use ContainerTrait, AttachableContainerTrait;

    public function get($class)
    {
        if (!$this->isKeyValid($class)) {
            throw new \InvalidArgumentException("Provided key, '{$class}' is invalid");
        }

        if (!$this->has($class)) {
            throw new \Onion\Framework\Dependency\Exception\UnknownDependency("Unknown dependency '{$class}'");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstanceWithoutConstructor();
        }

        $parent = $this->getDelegate();
        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $rawType = $this->formatType($parameter->getType());

            $type = trim($rawType, '?');
            $name = (string) $parameter->getName();
            $transformedType = $this->convertVariableName($type);
            $transformedName = $this->convertVariableName($name);

            if ($this->has($type) && $parameter->getType()->isBuiltin()) {
                $parameters[$parameter->getPosition()] = $this->get($type);
            } elseif ($parent && $parent->has($type) && $parameter->getType()->isBuiltin()) {
                $parameters[$parameter->getPosition()] = $parent->get($type);
            } elseif ($parent && $parent->has($transformedType) && $parameter->getType()->isBuiltin()) {
                $parameters[$parameter->getPosition()] = $parent->get($transformedType);
            } elseif ($parent && $parent->has($name)) {
                $parameters[$parameter->getPosition()] = $parent->get($name);
            } elseif ($parent && $parent->has($transformedName)) {
                $parameters[$parameter->getPosition()] = $parent->get($transformedName);
            } elseif ($parameter->isOptional()) {
                $parameter[$parameter->getPosition()] = $parameter->getDefaultValue();
            } else {
                throw new UnknownDependency("Unable to resolve {$parameter->getName()} ({$rawType})");
            }
        }

        return $this->enforceReturnType($class, $reflection->newInstanceArgs($parameters));
    }

    public function has($class)
    {
        return class_exists($class);
    }
}
