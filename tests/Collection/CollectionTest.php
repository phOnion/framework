<?php declare(strict_types=1);
namespace Tests\Collection;

use Onion\Framework\Collection\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testInitializationWithArray()
    {
        $this->assertInstanceOf(\Iterator::class, new Collection([], function ($item) {
            return $item;
        }));
    }

    public function testGeneratorResult()
    {
        $collection = new Collection([
            1, 2, 3, 4, 5
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            iterator_to_array($collection)
        );
    }

    public function testCollectionFiltering()
    {
        $collection = new Collection([
            1, 2, 3, 4, 5
        ]);
        $collection->setFilter(function ($item) {
            return ($item % 2) === 0;
        });

        $this->assertSame(
            [1 => 2, 3 => 4],
            iterator_to_array($collection)
        );
    }
}
