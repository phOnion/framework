<?php

declare(strict_types=1);

namespace Tests\Dependency\Doubles;

class DependencyJ
{
    public function __construct(DependencyK $test)
    {
    }
}
