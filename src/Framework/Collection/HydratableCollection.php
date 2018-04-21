<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class HydratableCollection implements \IteratorAggregate
{
    private $items;
    private $entityClass;

    public function __construct(iterable $items, string $entity)
    {
        $this->items = $items;
        if (!in_array(HydratableInterface::class, class_implements($entity), true)) {
            throw new \InvalidArgumentException(
                "Provided '{$entity}' does not exist or does not implement: " .
                    HydratableInterface::class
            );
        }

        $this->entityClass = $entity;
    }

    public function getIterator()
    {
        $entity = (new \ReflectionClass($this->entityClass))->newInstance();

        return new CallbackCollection($this->items, function ($item, $key) use ($entity) {
            /** @var HydratableInterface $entity */
            return $entity->hydrate($item);
        });
    }
}
