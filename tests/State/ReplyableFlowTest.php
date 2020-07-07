<?php
namespace Tests\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Onion\Framework\State\ReplyableFlow;
use Onion\Framework\State\Transition;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;

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

    public function testReply()
    {
        $wrapped = new Flow('test', 'foo');
        $wrapped->addTransition(new Transition('foo', 'bar', function () {
            $this->assertTrue(true);

            return true;
        }));

        $flow = new ReplyableFlow($wrapped);
        $this->assertSame('test', $flow->getName());
        $this->assertSame('foo', $flow->getState());
        $this->assertInstanceOf(HistoryInterface::class, $flow->getHistory());
        $this->assertTrue($flow->can('bar'));
        $this->assertTrue($flow->apply('bar', new \stdClass));
        $this->assertNotSame($flow, $flow->reset());
        $flow->reply();
    }

    public function testFailingReply()
    {
        $wrapped = new Flow('test', 'foo');
        $index = 0;
        $wrapped->addTransition(new Transition('foo', 'bar', function () use (&$index) {
            $this->assertTrue(true);

            return $index++ === 0;
        }));

        $flow = new ReplyableFlow($wrapped);
        $this->assertInstanceOf(HistoryInterface::class, $flow->getHistory());
        $this->assertTrue($flow->can('bar'));
        $this->assertTrue($flow->apply('bar', new \stdClass));

        $this->expectException(TransitionException::class);
        $flow->reply();
    }
}
