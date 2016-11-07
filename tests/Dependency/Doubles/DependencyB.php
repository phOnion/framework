<?php
declare(strict_types = 1);

namespace Tests\Dependency\Doubles;

class DependencyB
{
    public function __construct(DependencyC $c, $name = null)
    {
    }
}
