<?php declare(strict_types=1);
namespace Tests\Collection;

use Onion\Framework\Collection\CallbackCollection;

class CallbackCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectionFiltering()
    {
        $collection = new CallbackCollection([
            '1', '2', '3', '4', '5'
        ], function ($item) {
            return (int) $item;
        });
        $collection->setFilter(function ($item) {
            return ($item % 2) === 0;
        });

        $this->assertSame(
            [1 => 2, 3 => 4],
            iterator_to_array($collection)
        );
    }
}
