<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class HydratableCollection extends Collection
{
    public function __construct(iterable $items, HydratableInterface $prototype)
    {
        $items = new CallbackCollection($items, function ($item) use ($prototype) {
            /** @var HydratableInterface $prototype */
            return $prototype->hydrate($item);
        });

        parent::__construct($items);
    }
}
