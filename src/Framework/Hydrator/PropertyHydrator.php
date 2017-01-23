<?php
declare(strict_types=1);

namespace Onion\Framework\Hydrator;

trait PropertyHydrator
{
    /**
     * @inheritdoc
     */
    public function hydrate(array $data)
    {
        $target = clone $this;
        foreach ($data as $name => $value) {
            $property = str_replace('_', '', lcfirst(ucwords($name, '_')));
            if (property_exists($target, $property)) {
                $target->$property = $value;
            }
        }

        return $target;
    }

    /**
     * @inheritdoc
     */
    public function extract(array $keys = []): array
    {
        $data = [];
        if ($keys === []) {
            $reflection = new \ReflectionObject($this);
            foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $name => $value) {
                $data[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name))] = $value;
            }

            return $data;
        }

        foreach ($keys as $name => $value) {
            $data[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name))] = $value;
        }

        return $data;
    }
}
