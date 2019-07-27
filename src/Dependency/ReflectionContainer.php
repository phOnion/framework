<?php
namespace Onion\Framework\Dependency;

use Onion\Framework\Common\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Common\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class ReflectionContainer implements ContainerInterface, AttachableContainer
{
    use ContainerTrait, AttachableContainerTrait;

    public function get($class)
    {
        assert(
            $this->isKeyValid($class) && $this->has($class),
            new \InvalidArgumentException("Provided key, '{$class}' is invalid")
        );

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $parent = $this->getDelegate();
        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $rawType = $this->formatType($parameter->getType());

            $type = trim($rawType, '?');
            $name = (string) $parameter->getName();
            $transformedType = $this->convertVariableName($type);
            $transformedName = $this->convertVariableName($name);

            if ($this->has($type) && !$parameter->getType()->isBuiltin()) {
                try {
                    $parameters[$parameter->getPosition()] = $this->get($type);
                } catch (UnknownDependency $ex) {
                    if ($parent->has($type) && !$parameter->getType()->isBuiltin()) {
                        $parameters[$parameter->getPosition()] = $parent->get($type);
                    } elseif ($parent->has($transformedType) && !$parameter->getType()->isBuiltin()) {
                        $parameters[$parameter->getPosition()] = $parent->get($transformedType);
                    } elseif ($parent->has($name)) {
                        $parameters[$parameter->getPosition()] = $parent->get($name);
                    } elseif ($parent->has($transformedName)) {
                        $parameters[$parameter->getPosition()] = $parent->get($transformedName);
                    } elseif ($parameter->isOptional()) {
                        $parameters[$parameter->getPosition()] = $parameter->getDefaultValue();
                    } else {
                        throw new UnknownDependency("Unable to resolve parameter {$parameter->getName()} ({$rawType}) of {$class}");
                    }
                }
            } else {
                throw new UnknownDependency("Unable to resolve {$class}");
            }
        }

        return $this->enforceReturnType($class, $reflection->newInstanceArgs($parameters));
    }

    public function has($class)
    {
        assert(
            $this->isKeyValid($class),
            new \InvalidArgumentException("Provided key, '{$class}' is invalid")
        );

        return class_exists($class, true) || in_array($class, get_declared_classes());
    }
}
