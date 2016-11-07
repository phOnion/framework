<?php
declare(strict_types = 1);

namespace Tests\Dependency\Doubles;

class DependencyA
{
    public function __construct(DependencyB $dependency)
    {
    }
}
