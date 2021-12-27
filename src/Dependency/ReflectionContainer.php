<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Common\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Common\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionUnionType;

class ReflectionContainer implements ContainerInterface, AttachableContainer
{
    use ContainerTrait;
    use AttachableContainerTrait;

    public function get($class): mixed
    {
        assert(
            $this->isKeyValid($class) && class_exists($class),
            new \InvalidArgumentException("Provided key, '{$class}' is invalid")
        );

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $parent = $this->getDelegate();
        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $rawType = $this->formatType($parameter->getType());

            $type = trim($rawType, '?');
            $name = $parameter->getName();
            $transformedName = $this->convertVariableName($name);

            $typeReflection = $parameter->getType();
            try {
                if ($typeReflection !== null && $this->has($type) && ($typeReflection instanceof ReflectionUnionType || ($typeReflection instanceof ReflectionNamedType && !$typeReflection->isBuiltin()))) {
                    try {
                        $parameters[$parameter->getPosition()] = $this->get($type);
                    } catch (UnknownDependency $ex) {
                        $parameters[$parameter->getPosition()] = $parent->get($type);
                    }
                } elseif ($parent->has($transformedName)) {
                    $parameters[$parameter->getPosition()] = $parent->get($transformedName);
                } elseif ($parameter->isOptional()) {
                    $parameters[$parameter->getPosition()] = $parameter->getDefaultValue();
                } else {
                    throw new UnknownDependency(
                        "Unable to resolve parameter {$parameter->getName()} ({$rawType}) of {$class}"
                    );
                }
            } catch (UnknownDependency $ex) {
                throw new ContainerErrorException(
                    "Unable to resolve {$class}, missing \${$name}({$type})",
                    0,
                    $ex
                );
            }
        }

        return $this->enforceReturnType($class, $reflection->newInstanceArgs($parameters));
    }

    public function has($class): bool
    {
        assert(
            $this->isKeyValid($class),
            new \InvalidArgumentException("Provided key, '{$class}' is invalid")
        );

        return class_exists((string) $class, true);
    }
}
