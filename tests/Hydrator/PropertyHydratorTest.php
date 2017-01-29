<?php
declare(strict_types=1);

namespace Tests\Hydrator;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use Onion\Framework\Hydrator\PropertyHydrator;

class PropertyHydratorTest extends \PHPUnit_Framework_TestCase
{
    private $testable;

    public function setUp()
    {

    }

    public function testHydration()
    {
        /**
         * @var $testable HydratableInterface
         */
        $testable = new class implements HydratableInterface
        {
            use PropertyHydrator;

            public $id;
            public $name;
        };
        $result = $testable->hydrate([
            'id' => 10,
            'name' => 'George'
        ]);
        $this->assertNotSame($testable, $result);
        $this->assertSame(10, $result->id);
        $this->assertSame('George', $result->name);
    }

    public function testDehydration()
    {
        /**
         * @var $testable HydratableInterface
         */
        $testable = new class implements HydratableInterface
        {
            use PropertyHydrator;

            public $id = 10;
            public $name = 'John';
        };

        $this->assertSame([
            'id' => 10,
            'name' => 'John'
        ], $testable->extract());
    }

    public function testNamingTransformation()
    {
        /**
         * @var $testable HydratableInterface
         */
        $testable = new class implements HydratableInterface
        {
            use PropertyHydrator;

            public $firstName = 'Jane';
        };

        $this->assertArrayHasKey('first_name', $testable->extract());
        $result = $testable->hydrate(['first_name' => 'Jenny']);
        $this->assertSame('Jenny', $result->firstName);
    }

    public function testSelectiveExtraction()
    {
        /**
         * @var $testable HydratableInterface
         */
        $testable = new class implements HydratableInterface
        {
            use PropertyHydrator;

            public $firstName = 'Jane';
        };

        $this->assertSame(['first_name' => 'Jane'], $testable->extract(['first_name']));
    }
}
