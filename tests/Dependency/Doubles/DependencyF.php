<?php
declare(strict_types = 1);

namespace Tests\Dependency\Doubles;

class DependencyF
{
    public function __construct(UnmappedDependency $dependency)
    {
    }
}
