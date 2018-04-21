<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class HydratableCollection extends CallbackCollection
{
    private $items;
    private $entityClass;

    public function __construct(iterable $items, string $entity)
    {
        if (!class_exists($entity)) {
            throw new \InvalidArgumentException(
                "Provided entity '{$entity}' does not exist"
            );
        }
        $reflection = new \ReflectionClass($this->entityClass);
        if (!$reflection->implementsInterface(HydratableInterface::class)) {
            throw new \InvalidArgumentException(
                "Provided '{$entity}' does not implement: " . HydratableInterface::class
            );
        }

        $prototype = $reflection->newInstance();
        parent::__construct($items, function ($item, $key) use ($prototype) {
            /** @var HydratableInterface $prototype */
            return $prototype->hydrate($item);
        });
    }
}
