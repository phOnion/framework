<?php
namespace Tests\State;

use PHPUnit\Framework\TestCase;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Prophecy\Argument\Token\TypeToken;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Exceptions\TransitionException;

class FlowTest extends TestCase
{
    public function testBaseFunctionality()
    {
        $flow = new Flow('foo', 'bar');
        $this->assertSame('bar', $flow->getState());
        $this->assertSame('foo', $flow->getName());
        $this->assertCount(0, $flow->getHistory());
        $this->assertFalse($flow->can('test'));
        $this->assertEmpty($flow->getPossibleTransitions());
        $this->assertNotSame($flow, $flow->reset());
    }

    public function testBaseTransitioning()
    {
        $t1 = $this->prophesize(TransitionInterface::class);
        $t1->getSource()->willReturn('bar')->shouldBeCalledOnce();
        $t1->getDestination()->willReturn('baz')->shouldBeCalledOnce();
        $t1->__invoke(new TypeToken(\stdClass::class))->willReturn(true)->shouldBeCalledOnce();
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
        $t1->__invoke(new TypeToken(\stdClass::class))->willReturn(false)->shouldBeCalledOnce();
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
        $t1->__invoke(new TypeToken(\stdClass::class))->willThrow(new \Exception('OK'))->shouldBeCalledOnce();
        $t1->withArguments(new TypeToken(\stdClass::class))->willReturn($t1->reveal())->shouldBeCalledOnce();

        $flow = new Flow('test', 'foo');
        $flow->addTransition($t1->reveal());

        $flow->apply('bar', new \stdClass);
    }
}
