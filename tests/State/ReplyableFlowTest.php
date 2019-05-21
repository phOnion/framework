<?php
namespace Tests\State;

use PHPUnit\Framework\TestCase;
use Onion\Framework\State\Interfaces\FlowInterface;
use Prophecy\Argument\Token\TypeToken;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\ReplyableFlow;

class ReplyableFlowTest extends TestCase
{
    public function testWrappedMethods()
    {
        $wrapped = $this->prophesize(FlowInterface::class);
        $wrapped->addTransition(new TypeToken(TransitionInterface::class))
            ->shouldBeCalledOnce();
        $wrapped->can(new TypeToken('string'))
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $wrapped->apply(new TypeToken('string'), new TypeToken('object'))
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $wrapped->getName()
            ->willReturn('test')
            ->shouldBeCalledOnce();
        $wrapped->getState()
            ->willReturn('foo')
            ->shouldBeCalledOnce();
        $wrapped->getHistory()
            ->willReturn($this->prophesize(HistoryInterface::class)->reveal())
            ->shouldBeCalledOnce();
        $wrapped->reset()
            ->willReturn($wrapped->reveal())
            ->shouldBeCalledOnce();

        $flow = new ReplyableFlow($wrapped->reveal());
        $this->assertSame('test', $flow->getName());
        $this->assertSame('foo', $flow->getState());
        $this->assertInstanceOf(HistoryInterface::class, $flow->getHistory());
        $this->assertTrue($flow->apply('bar', new \stdClass));
        $this->assertTrue($flow->can('bar'));
        $this->assertNotSame($flow, $flow->reset());
        $flow->addTransition($this->prophesize(TransitionInterface::class)->reveal());
    }
}
