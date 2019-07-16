<?php
namespace Tests\State;

use PHPUnit\Framework\TestCase;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Onion\Framework\State\History;

class HistoryTest extends TestCase
{
    public function testHistoryPush()
    {
        $t1 = $this->prophesize(TransitionInterface::class)->reveal();
        $t2 = $this->prophesize(TransitionInterface::class)->reveal();

        $history = new History;
        $history->add($t1);
        $history->add($t2);

        $this->assertCount(2, $history);

        foreach ($history as $index => $t) {
            $name = 't' . ($index+1);
            $this->assertSame($$name, $t);
        }
    }
}
