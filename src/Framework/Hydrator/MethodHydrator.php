<?php
declare(strict_types=1);

namespace Onion\Framework\Hydrator;

/**
 * Hydrates and extracts objects using getters and setters
 */
trait MethodHydrator
{
    /**
     * Hydrates the object with the $data provided
     *
     * @param array $data Assoc array with param
     *
     * @return $this A hydrated copy of the object provided
     */
    public function hydrate(array $data)
    {
        $target = clone $this;
        foreach ($data as $name => $value) {
            // Transform underscored keys to camelCase
            $method = str_replace('_', '', ucfirst(ucwords($name, '_')));
            if (method_exists($target, 'set' . $method)) {
                $target->{'set' . $method}(...(array) $value);
            }
        }

        return $target;
    }

    /**
     * Extracts all data from the $object or extracts only
     * the provided $keys
     *
     * @param array  $keys List of keys with which to filter the extracted keys
     *
     * @return array The extracted data
     */
    public function extract(array $keys = []): array
    {
        $data = [];

        if ($keys === []) {
            $reflection = new \ReflectionObject($this);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (0 === strpos($method->getName(), 'get')) {
                    $data[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($method->getName(), 3)))] =
                        $method->invoke($this);
                }
            }

            return $data;
        }

        foreach ($keys as $name) {
            if (method_exists($this, 'get' . ucfirst(str_replace('_', '', ucwords($name))))) {
                $data[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name))] = call_user_func([
                    $this,
                    'get' . str_replace('_', '', ucfirst(ucwords($name, '_')))
                ]);
            }
        }

        return $data;
    }
}
