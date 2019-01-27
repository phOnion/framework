<?php declare(strict_types=1);
namespace Tests\Collection;

use Onion\Framework\Collection\HydratableCollection;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use Onion\Framework\Hydrator\PropertyHydrator;

class HydratableCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testCollection()
    {
        $prototype = new class implements HydratableInterface {
            use PropertyHydrator;

            public $name;
            public $age;
        };

        $collection = new HydratableCollection([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 27],
        ], $prototype);

        $this->assertContainsOnlyInstancesOf(
            HydratableInterface::class,
            iterator_to_array($collection)
        );
        $this->assertNotSame(...iterator_to_array($collection));
    }

    public function testCollectionFiltering()
    {
        $prototype = new class implements HydratableInterface {
            use PropertyHydrator;

            public $name;
            public $age;
        };

        $collection = new HydratableCollection([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 27],
        ], $prototype);
        $collection->setFilter(function ($item) {
            return $item->age > 25;
        });

        $this->assertContainsOnlyInstancesOf(
            HydratableInterface::class,
            iterator_to_array($collection)
        );
        $this->assertCount(1, iterator_to_array($collection));
    }
}
