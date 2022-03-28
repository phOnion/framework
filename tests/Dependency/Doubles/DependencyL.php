<?php

declare(strict_types=1);

namespace Tests\Dependency\Doubles;

class DependencyL
{
    public function __construct(DependencyA | DependencyB $x)
    {
    }
}
