<?php

namespace Tests\State;

use PHPUnit\Framework\TestCase;
use Onion\Framework\State\History;
use Prophecy\PhpUnit\ProphecyTrait;

class HistoryTest extends TestCase
{
    use ProphecyTrait;

    public function testHistoryPush()
    {
        $t1 = ['t', 'z', []];
        $t2 = ['z', 'v', []];

        $history = new History;
        $history->add('t', 'z', []);
        $history->add('z', 'v', []);

        $this->assertCount(2, $history);

        foreach ($history as $index => $t) {
            $name = 't' . ($index + 1);
            $this->assertSame($$name, $t);
        }
    }
}
