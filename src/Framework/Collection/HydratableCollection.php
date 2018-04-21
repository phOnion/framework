<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class HydratableCollection extends CallbackCollection
{
    public function __construct(iterable $items, HydratableInterface $prototype)
    {
        parent::__construct($items, function ($item, $key) use ($prototype) {
            /** @var HydratableInterface $prototype */
            return $prototype->hydrate($item);
        });
    }
}
