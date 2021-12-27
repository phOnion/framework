<?php

declare(strict_types=1);

namespace Tests\Dependency;

use Onion\Framework\Dependency\Container as OnionContainer;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer as Container;
use Onion\Framework\Dependency\ProxyContainer;
use Onion\Framework\Dependency\ReflectionContainer;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;
use Tests\Dependency\Doubles\DependencyA;
use Tests\Dependency\Doubles\DependencyC;
use Tests\Dependency\Doubles\DependencyD;
use Tests\Dependency\Doubles\DependencyE;
use Tests\Dependency\Doubles\DependencyF;
use Tests\Dependency\Doubles\DependencyJ;

class DelegateContainerTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testBasicConstruction()
    {
        $c = $this->prophesize(Container::class);
        $proxy = new ProxyContainer;
        $proxy->attach($c->reveal());
        $proxy->attach($c->reveal());
        $this->assertCount(2, $proxy);
    }

    public function testExistanceInNthContainer()
    {
        $c = $this->prophesize(Container::class);
        $c->attach(new AnyValueToken())->shouldBeCalledTimes(4);
        $c1 = $c->reveal();
        $c->has('foo')->willReturn(true);

        $delegate = new ProxyContainer;
        $delegate->attach($c1);
        $delegate->attach($c1);
        $delegate->attach($c->reveal());
        $delegate->attach($c1);
        $this->assertTrue($delegate->has('foo'));
    }

    public function testRetrievalFromNthContainer()
    {
        $c = $this->prophesize(Container::class);
        $c->has('foo')->willReturn(false);
        $c->attach(new AnyValueToken())->shouldBeCalledTimes(2);

        $delegate = new ProxyContainer;
        $delegate->attach($c->reveal());
        $delegate->attach($c->reveal());

        $this->assertFalse($delegate->has('foo'));

        $this->expectException(UnknownDependency::class);
        $this->expectExceptionMessage('Unable to resolve \'foo\'');
        $delegate->get('foo');
    }

    public function testAggregatedRetrieval()
    {
        $c = $this->prophesize(Container::class);
        $c->has('list')->willReturn(true);
        $c->get('list')->willReturn([
            'foo' => 'bar',
        ]);
        $c->has('foo')->willReturn(false);
        $c->attach(new AnyValueToken())->shouldBeCalledOnce();

        $c1 = $this->prophesize(Container::class);
        $c1->has('list')->willReturn(true);
        $c1->get('list')->willReturn([
            'bar' => 'baz',
        ]);
        $c1->has('foo')->willReturn(true);
        $c1->get('foo')->willReturn('bar');
        $c1->attach(new AnyValueToken())->shouldBeCalledOnce();

        $container = new ProxyContainer;
        $container->attach($c->reveal());
        $container->attach($c1->reveal());

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $container->get('list'));
        $this->assertSame('bar', $container->get('foo'));
    }

    public function testRetrievalExceptionWithoutContainers()
    {
        $delegate = new ProxyContainer();
        $this->assertFalse($delegate->has('foo'));

        $this->expectException(UnknownDependency::class);
        $this->expectExceptionMessage('No containers provided, can\'t retrieve \'foo\'');
        $delegate->get('foo');
    }

    public function testResolutionParity()
    {
        $delegate = new ProxyContainer();
        $container = new ReflectionContainer();
        $dummy = $this->prophesize(Container::class);
        $dummy->has('string')->willReturn(false);
        $dummy->has('name')->willReturn(true);
        $dummy->get('name')->willReturn('foo');
        $dummy->attach(new TypeToken(ProxyContainer::class));
        $dummy->has(DependencyE::class)->willReturn(true);
        $dummy->get(DependencyE::class)->willThrow(new UnknownDependency(''));

        $delegate->attach($dummy->reveal());
        $delegate->attach($container);

        $this->assertTrue($delegate->has(DependencyE::class));
        $this->assertInstanceOf(DependencyE::class, $delegate->get(DependencyE::class));
        $this->assertSame('foo', $delegate->get(DependencyE::class)->getName());
    }

    public function testFailedResolution()
    {
        $delegate = new ProxyContainer();
        $dummy = $this->prophesize(Container::class);
        $dummy->has('foo')->willReturn(true);
        $dummy->get('foo')->willThrow(new UnknownDependency(''));
        $dummy->attach(new TypeToken(ProxyContainer::class));
        $delegate->attach($dummy->reveal());

        $this->assertTrue($delegate->has('foo'));
        $this->expectException(ContainerErrorException::class);
        $delegate->get('foo');
    }

    public function testMetaResolution()
    {
        $container = new OnionContainer([
            'invokables' => [
                \Tests\Dependency\Doubles\DependencyK::class => new stdClass
            ]
        ]);
        $delegate = new ProxyContainer;
        $delegate->attach($container);

        $this->assertTrue($delegate->has(\Tests\Dependency\Doubles\DependencyK::class));
        $this->assertTrue($delegate->has(DependencyJ::class));
        $this->assertTrue($delegate->has(\Tests\Dependency\Doubles\DependencyK::class));
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('stdClass given');
        $delegate->get(DependencyJ::class);
    }

    public function testDependencyNameResolutionFromReflectionException()
    {
        $delegate = new ProxyContainer();
        $container = new ReflectionContainer();
        $dummy = $this->prophesize(Container::class);
        $dummy->attach($delegate)->shouldBeCalledOnce();
        $dummy->has('name')->willReturn(false);
        $dummy->has(DependencyC::class)->willReturn(false);
        $dummy->has('c')->willReturn(true);
        $dummy->get('c')->willReturn($this->prophesize(DependencyC::class)->reveal());
        $delegate->attach($container);
        $delegate->attach($dummy->reveal());

        $this->assertFalse($delegate->has(DependencyC::class));
        $this->assertInstanceOf(DependencyA::class, $container->get(DependencyA::class));
    }

    public function testDependencyTypeResolutionFromReflectionException()
    {
        $key = \Tests\Dependency\Doubles\DependencyK::class;
        $delegate = new ProxyContainer();
        $container = new OnionContainer([
            'factories' => [
                $key => function ($c) {
                    return new stdClass;
                },
            ]
        ]);
        $dummy = $this->prophesize(Container::class);
        $dummy->attach($delegate)->shouldBeCalledOnce();
        // $dummy->has('name')->willReturn(false);
        $dummy->has('test')->willReturn(false);
        $dummy->has(DependencyJ::class)->willReturn(true);
        $dummy->has($key)->willReturn(false);
        $dummy->get(DependencyJ::class)->willReturn($this->prophesize(DependencyJ::class)->reveal());
        $delegate->attach($container);
        $delegate->attach($dummy->reveal());

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('stdClass given');
        $this->assertInstanceOf(DependencyA::class, $container->get(DependencyJ::class));
    }
}
