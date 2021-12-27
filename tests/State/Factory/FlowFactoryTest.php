<?php

namespace Tests\State\Factory;

use Onion\Framework\Common\Config\Container;
use Onion\Framework\State\Factory\FlowFactory;
use Onion\Framework\State\Interfaces\FlowInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class FlowFactoryTest extends TestCase
{
    private $container;

    use ProphecyTrait;

    public function setUp(): void
    {
        $config = $this->prophesize(Container::class);
        $config->get('initial')->willReturn('foo');
        $config->get('transitions')->willReturn([
            [
                'source' => 'foo',
                'destination' => 'bar',
            ], [
                'source' => 'bar',
                'destination' => 'baz',
            ]
        ]);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('states.test')->willReturn($config->reveal());

        $this->container = $container->reveal();
    }

    public function testFlowBuilding()
    {
        $factory = new FlowFactory;
        /** @var FlowInterface $flow */
        $flow = $factory->build($this->container, 'test')->build($this->container);

        $this->assertInstanceOf(FlowInterface::class, $flow);
        $this->assertTrue($flow->can('bar'));
        $this->assertFalse($flow->can('baz'));
        $this->assertTrue($flow->apply('bar', $this));
    }
}
