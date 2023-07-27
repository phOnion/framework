<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

class ReflectionContainer implements ContainerInterface, AttachableContainer
{
    use AttachableContainerTrait;
    use ContainerTrait;

    public function get(string $id): mixed
    {
        \assert(
            \class_exists($id),
            new UnknownDependencyException("Provided key '{$id}' is not a FQN of a class or could not be auto-loaded")
        );

        $reflection = new ReflectionClass($id);
        $parameters = [];

        try {
            foreach (($reflection->getConstructor()?->getParameters() ?? []) as $parameter) {
                /** @var \ReflectionParameter $parameter */
                $type = $parameter->getType();

                if (($type instanceof ReflectionNamedType && !$type->isBuiltin())) {
                    \assert(
                        $this->getDelegate()->has($type->getName()) || $type->allowsNull(),
                        new UnknownDependencyException(sprintf(
                            "Unable to resolve non-nullable type '\$%s(%s)'",
                            $parameter->getName(),
                            $type->getName(),
                            $id,
                        )),
                    );

                    $parameters[$parameter->getPosition()] =
                        $this->getDelegate()->has($type->getName()) ?
                        $this->getDelegate()->get($type->getName()) : null;
                } elseif ($parameter->isOptional()) {
                    $parameters[$parameter->getPosition()] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull() && $parameter->getType()) {
                    $parameters[$parameter->getPosition()] = null;
                } elseif ($this->getDelegate()->has($parameter->getName())) {
                    $parameters[$parameter->getPosition()] = $this->getDelegate()->get(
                        $this->convertVariableName($parameter->getName())
                    );
                } else {
                    $typeName = $type instanceof ReflectionUnionType ?
                        \implode(' | ', $type->getTypes()) : ($type instanceof \ReflectionNamedType ?
                            $type->getName() :
                            'unknown'
                        );
                    throw new UnknownDependencyException(
                        "Missing \${$parameter->getName()}({$typeName})"
                    );
                }
            }

            return $reflection->newInstance(...$parameters);
        } catch (UnknownDependencyException $ex) {
            throw new UnknownDependencyException(\sprintf(
                'Unable to resolve %s: %s',
                $id,
                $ex->getMessage()
            ), previous: $ex);
        } catch (ContainerErrorException $ex) {
            throw new ContainerErrorException(
                "Unable to build dependency {$id}",
                previous: $ex
            );
        }
    }

    public function has(string $id): bool
    {
        return \class_exists($id, true);
    }
}
