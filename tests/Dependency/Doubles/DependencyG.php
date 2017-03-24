<?php
declare(strict_types=1);

namespace Tests\Dependency\Doubles;

class DependencyG
{
    private $name;
    public function __construct(string $testMockName)
    {
        $this->name = $testMockName;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }
}
