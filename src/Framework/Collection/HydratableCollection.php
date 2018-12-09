<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class HydratableCollection extends Collection
{
    /**
     * @param mixed[] $items
     * @param HydratableInterface $prototype
     **/
    public function __construct(iterable $items, HydratableInterface $prototype)
    {
        $items = new CallbackCollection($items, function (array $item) use ($prototype): object {
            /** @var HydratableInterface $prototype */
            return $prototype->hydrate($item);
        });

        parent::__construct($items);
    }
}
