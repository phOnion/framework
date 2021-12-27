<?php

namespace Tests\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

class FlowTest extends TestCase
{
    use ProphecyTrait;

    public function testBaseFunctionality()
    {
        $transition = $this->prophesize(TransitionInterface::class);
        $transition->getSource()->willReturn('bar');
        $transition->getDestination()->willReturn('baz');
        $transition->withArguments(new stdClass)
            ->willReturn($transition->reveal())
            ->shouldBeCalledOnce();
        $transition->__invoke()->willReturn(true);

        $flow = new Flow('foo', 'bar');
        $flow->addTransition($transition->reveal());
        $this->assertSame('bar', $flow->getState());
        $this->assertSame('foo', $flow->getName());
        $this->assertCount(0, $flow->getHistory());
        $this->assertFalse($flow->can('test'));
        $this->assertNotEmpty($flow->getPossibleTransitions());
        $this->assertTrue($flow->apply('baz', new stdClass));
        $this->assertNotSame($flow, $flow->reset());
        $this->assertNotSame($flow->getState(), $flow->reset()->getState());
        $this->assertTrue($flow->reset()->can('baz'));
    }

    public function testBaseTransitioning()
    {
        $t1 = $this->prophesize(TransitionInterface::class);
        $t1->getSource()->willReturn('bar')->shouldBeCalledOnce();
        $t1->getDestination()->willReturn('baz')->shouldBeCalledOnce();
        $t1->__invoke()->willReturn(true)->shouldBeCalledOnce();
        $t1->withArguments(new TypeToken(\stdClass::class))->willReturn($t1->reveal())->shouldBeCalledOnce();

        $flow = new Flow('foo', 'bar');
        $flow->addTransition($t1->reveal());

        $this->assertTrue($flow->can('baz'));
        $this->assertCount(0, $flow->getHistory());
        $this->assertSame(['baz'], $flow->getPossibleTransitions());
        $this->assertTrue($flow->apply('baz', new \stdClass));
        $this->assertCount(1, $flow->getHistory());
        $this->assertEmpty($flow->getPossibleTransitions());
    }

    public function testFailedTransitionHandler()
    {
        $t1 = $this->prophesize(TransitionInterface::class);
        $t1->getSource()->willReturn('bar')->shouldBeCalledOnce();
        $t1->getDestination()->willReturn('baz')->shouldBeCalledOnce();
        $t1->__invoke()->willReturn(false)->shouldBeCalledOnce();
        $t1->withArguments(new TypeToken(\stdClass::class))->willReturn($t1->reveal())->shouldBeCalledOnce();

        $flow = new Flow('foo', 'bar');
        $flow->addTransition($t1->reveal());


        $this->assertTrue($flow->can('baz'));
        $this->assertCount(0, $flow->getHistory());
        $this->assertFalse($flow->apply('baz', new \stdClass));
        $this->assertCount(1, $flow->getHistory());
    }

    public function testUndefinedTransition()
    {
        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage(
            'Moving from \'foo\' to \'bar\''
        );

        try {
            $flow = new Flow('test', 'foo');
            $flow->apply('bar', new \stdClass);
        } catch (TransitionException $ex) {
            $this->assertInstanceOf(HistoryInterface::class, $ex->getHistory());
            $this->assertCount(1, $ex->getHistory());

            throw $ex;
        }
    }

    public function testFailedTransition()
    {
        $this->expectException(TransitionException::class);

        $t1 = $this->prophesize(TransitionInterface::class);
        $t1->getSource()->willReturn('foo')->shouldBeCalledOnce();
        $t1->getDestination()->willReturn('bar')->shouldBeCalledOnce();
        $t1->__invoke()->willThrow(new \Exception('OK'))->shouldBeCalledOnce();
        $t1->withArguments(new TypeToken(\stdClass::class))->willReturn($t1->reveal())->shouldBeCalledOnce();

        $flow = new Flow('test', 'foo');
        $flow->addTransition($t1->reveal());

        $flow->apply('bar', new \stdClass);
    }
}
