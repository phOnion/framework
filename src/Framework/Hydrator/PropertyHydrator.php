<?php declare(strict_types=1);
namespace Onion\Framework\Hydrator;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

trait PropertyHydrator
{
    /**
     * Hydrates the object with the $data provided
     *
     * @param iterable $data Param
     *
     * @return self|HydratableInterface A hydrated copy of the object provided
     */
    public function hydrate(iterable $data): HydratableInterface
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
    public function extract(iterable $keys = []): iterable
    {
        $data = [];
        if ($keys === []) {
            $reflection = new \ReflectionObject($this);
            foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                $data[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property->getName()))] =
                    $property->getValue($this);
            }

            return $data;
        }

        foreach ($keys as $name) {
            $data[$name] = $this->{str_replace('_', '', lcfirst(ucwords($name, '_')))};
        }

        return $data;
    }
}
