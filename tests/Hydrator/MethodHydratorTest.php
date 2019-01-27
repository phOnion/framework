<?php
declare(strict_types=1);

namespace Tests\Hydrator;

use \Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use \Onion\Framework\Hydrator\MethodHydrator;

class MethodHydratorTest extends \PHPUnit\Framework\TestCase
{
    public function testHydration()
    {
        /**
         * @var HydratableInterface $hydrator
         */
        $testable = new class implements HydratableInterface
        {
            use MethodHydrator;

            private $id;
            private $name;

            public function getId(): int
            {
                return $this->id;
            }

            public function setId(int $id)
            {
                $this->id = $id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function setName(string $name)
            {
                $this->name = $name;
            }
        };

        $result = $testable->hydrate([
            'id' => 10,
            'name' => 'George'
        ]);

        $this->assertNotSame($testable, $result);
        $this->assertSame(10, $result->getId());
        $this->assertSame('George', $result->getName());
    }

    public function testDehydration()
    {
        $testable = new class implements HydratableInterface
        {
            use MethodHydrator;

            public function getId(): int { return 10; }
            public function getName(): string { return 'John'; }
        };

        $this->assertSame([
            'id' => 10,
            'name' => 'John'
        ], $testable->extract());
    }

    public function testNamingTransformation()
    {
        $testable = new class implements HydratableInterface
        {
            use MethodHydrator;

            private $name = 'Jane';
            public function getFirstName(): string
            {
                return (string) $this->name;
            }
        };

       $this->assertSame(['first_name' => 'Jane'], $testable->extract(['first_name']));
    }
}
