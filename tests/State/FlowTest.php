<?php

namespace Tests\State;

use Countable;
use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

class FlowTest extends TestCase
{
    use ProphecyTrait;

    public function testBaseFunctionality()
    {
        $flow = new Flow('foo', 'bar');
        $flow->addTransition('bar', 'baz', fn () => true);
        $this->assertSame('bar', $flow->getState());
        $this->assertSame('foo', $flow->name);
        $this->assertNull($flow->history);
        $this->assertFalse($flow->can('test'));
        $this->assertNotNull($flow->getBranches());
        $this->assertTrue($flow->apply('baz', new stdClass));
        $this->assertNotSame($flow, $flow->reset());
        $this->assertNotSame($flow->getState(), $flow->reset()->getState());
        $this->assertTrue($flow->reset()->can('baz'));
    }

    public function testBaseTransitioning()
    {
        $history = $this->prophesize(HistoryInterface::class);
        $history->add('bar', 'baz', [])->shouldBeCalledOnce();
        $flow = new Flow('foo', 'bar', $history->reveal());
        $flow->addTransition('bar', 'baz', fn () => true);
        $flow->addTransition('bar', 'bam', fn () => true);

        $this->assertTrue($flow->can('baz'));
        $this->assertSame(['baz', 'bam'], $flow->reset()->getBranches());
        $this->assertTrue($flow->apply('baz', new \stdClass));
        $this->assertNull($flow->getBranches());
    }

    public function testFailedTransitionHandler()
    {
        $history = $this->prophesize(HistoryInterface::class);
        $history->add('bar', 'baz', [])->shouldBeCalledOnce();
        $flow = new Flow('foo', 'bar', $history->reveal());
        $flow->addTransition('bar', 'baz', fn () => false);


        $this->assertTrue($flow->can('baz'));
        $this->assertFalse($flow->apply('baz', new \stdClass));
    }

    public function testUndefinedTransition()
    {
        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage(
            'Moving from \'foo\' to \'bar\''
        );
        $history = $this->prophesize(HistoryInterface::class);
        $history->add('foo', 'bar', [])->shouldBeCalledOnce();
        $history = $history->reveal();

        try {
            $flow = new Flow('test', 'foo', $history);
            $flow->apply('bar', new \stdClass);
        } catch (TransitionException $ex) {
            $this->assertInstanceOf(HistoryInterface::class, $ex->getHistory());
            $this->assertSame($history, $ex->getHistory());

            throw $ex;
        }
    }

    public function testExceptionDuringTransition()
    {
        $this->expectException(TransitionException::class);
        $this->expectExceptionMessage("Transition from 'foo' to 'bar' failed");

        $history = $this->prophesize(HistoryInterface::class);
        $history->add('foo', 'bar', [])->shouldBeCalledOnce();
        $history = $history->reveal();

        try {
            $flow = new Flow('test', 'foo', $history);
            $flow->addTransition('foo', 'bar', function () {
                throw new \RuntimeException('Test');
            });
            $flow->apply('bar', new \stdClass);
        } catch (TransitionException $ex) {
            $this->assertInstanceOf(HistoryInterface::class, $ex->getHistory());
            $this->assertSame($history, $ex->getHistory());

            throw $ex;
        }
    }

    public function testFailedTransition()
    {
        $this->expectException(TransitionException::class);

        $flow = new Flow('test', 'foo');
        $flow->addTransition('foo', 'bar', function () {
            throw new \Exception('OK');
        });

        $flow->apply('bar', new \stdClass);
    }
}
