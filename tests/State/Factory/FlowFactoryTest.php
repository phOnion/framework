<?php

namespace Tests\State\Factory;

use Onion\Framework\Config\Container;
use Onion\Framework\State\Factory\FlowFactory;
use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class FlowFactoryTest extends TestCase
{
    private $container;

    use ProphecyTrait;

    public function setUp(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(HistoryInterface::class)->shouldBeCalledOnce()->willReturn(
            $this->prophesize(HistoryInterface::class)->reveal()
        );
        $container->get('workflows.test.states')->willReturn([
            [
                'from' => 'foo',
                'to' => 'bar',
                'handler' => fn () => true,
            ], [
                'from' => 'bar',
                'to' => 'baz',
                'handler' => fn () => true,
            ]
        ]);
        $container->has('workflows.test.history')->willReturn(true);
        $container->get('workflows.test.history')->willReturn(HistoryInterface::class);
        $container->get('workflows.test.initial')->willReturn('foo');

        $this->container = $container->reveal();
    }

    public function testFlowBuilding()
    {
        $factory = new FlowFactory;
        /** @var FlowInterface $flow */
        $flow = $factory->build($this->container, 'test');

        $this->assertInstanceOf(FlowInterface::class, $flow);
        $this->assertTrue($flow->can('bar'));
        $this->assertFalse($flow->can('baz'));
        $this->assertTrue($flow->apply('bar', $this));
    }
}
