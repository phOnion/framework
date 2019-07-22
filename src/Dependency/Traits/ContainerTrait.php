<?php
namespace Onion\Framework\Dependency\Traits;

use Onion\Framework\Dependency\Exception\ContainerErrorException;

trait ContainerTrait
{
    protected function convertVariableName(string $name): string
    {
        return str_replace('\\', '', strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $name)));
    }

    protected function enforceReturnType(string $identifier, object $result): object
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

    protected function isKeyValid($key): bool
    {
        return is_string($key) || is_scalar($key) ||
            (is_object($key) && method_exists($key, '__toString'));
    }

    protected function formatType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }

        return $type->allowsNull() ? "?{$type}" : (string) $type;
    }
}
