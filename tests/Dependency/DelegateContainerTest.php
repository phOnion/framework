<?php declare(strict_types=1);
namespace Tests\Dependency;

use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer as Container;
use Onion\Framework\Dependency\ProxyContainer;
use Prophecy\Argument\Token\AnyValueToken;

class DelegateContainerTest extends \PHPUnit\Framework\TestCase
{
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
}
