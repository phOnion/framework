<?php

namespace Tests\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Flow;
use Onion\Framework\State\History;
use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Onion\Framework\State\RepeatableFlow;
use Onion\Framework\State\Transition;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

class RepeatableFlowTest extends TestCase
{
    use ProphecyTrait;

    public function testReply()
    {
        $i = 0;
        $history = $this->prophesize(HistoryInterface::class);
        $history->add('foo', 'bar', [])->shouldBeCalledOnce();
        $history->valid()->willReturn(true, false);
        $history->current()->willReturn(['foo', 'bar', []]);
        $history->rewind();
        $history->next();
        $history->key()->willReturn(0, 1);

        $flow = new RepeatableFlow('test', 'foo', $history->reveal());
        $flow->addTransition('foo', 'bar', function (stdClass &$object) use (&$i) {
            $object->foo = $i++;

            return true;
        });

        $std1 = new stdClass;
        $std2 = new stdClass;

        $flow->apply('bar', $std1);
        // $this->assertObjectHasAttribute('foo', $std1);
        // $this->assertObjectHasAttribute('foo', $std2);
        $flow->reply($std2);
        $this->assertNotSame($std1->foo, $std2->foo);
    }

    public function testFailingReply()
    {
        $flow = new RepeatableFlow('test', 'foo', new History());
        $index = 0;
        $flow->addTransition('foo', 'bar', function () use (&$index) {
            $this->assertTrue(true);

            return $index++ === 0;
        });

        $this->assertTrue($flow->apply('bar', new \stdClass));

        $this->expectException(TransitionException::class);
        $flow->reply(new stdClass);
    }
}
