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

    public function testNewFilteredCollection()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $filteredCollection = $collection->filter(function ($i) { return ($i%2) === 0; });
        $this->assertNotSame($collection, $filteredCollection);
        $this->assertSame(
            [1 => 2, 3 => 4],
            iterator_to_array($filteredCollection)
        );
    }

    public function testCollectionMapping()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $mapped = $collection->map(function ($item) {
            return $item**2;
        });

        $this->assertNotSame($collection, $mapped);
        $this->assertSame(
            [1, 4, 9, 16, 25],
            iterator_to_array($mapped)
        );
    }

    public function testCollectionSlicing()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $slice = $collection->slice(2, 2);

        $this->assertNotSame($collection, $slice);
        $this->assertSame(
            [3, 4],
            iterator_to_array($slice)
        );
    }

    public function testCollectionCounting()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertCount(5, $collection);
        $filtered = $collection->filter(function ($i) {
            return ($i%2) !== 0;
        });
        $this->assertCount(3, $filtered);
    }

    public function testCollectionSorting()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $sorted = $collection->sort(function ($a, $b) {
            return $b <=> $a;
        });

        $this->assertNotSame($collection, $sorted);
        $this->assertSame(
            [5, 4, 3, 2, 1],
            iterator_to_array($sorted)
        );
    }
}
