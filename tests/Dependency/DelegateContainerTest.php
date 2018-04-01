<?php declare(strict_types=1);
namespace Tests\Dependency;

use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\DelegateContainer;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer as Container;
use Prophecy\Argument\Token\AnyValueToken;

class DelegateContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicConstruction()
    {
        $c = $this->prophesize(Container::class);
        $this->assertCount(2, new DelegateContainer([
            $c->reveal(),
            $c->reveal()
        ]));
    }

    public function testExistanceInNthContainer()
    {
        $c = $this->prophesize(Container::class);
        $c->attach(new AnyValueToken())->willReturn(null);
        $c1 = $c->reveal();
        $c->has('foo')->willReturn(true);

        $this->assertTrue((
            new DelegateContainer([$c1, $c1, $c->reveal(), $c1])
        )->has('foo'));
    }

    public function testRetrievalFromNthContainer()
    {
        $c = $this->prophesize(Container::class);
        $c->has('foo')->willReturn(false);
        $c->attach(new AnyValueToken())->willReturn(null);

        $delegate = new DelegateContainer([$c->reveal(), $c->reveal()]);
        $this->assertFalse($delegate->has('foo'));

        $this->expectException(UnknownDependency::class);
        $this->expectExceptionMessage('Unable to resolve \'foo\'');
        $delegate->get('foo');
    }

    public function testRetrievalExceptionWithoutContainers()
    {
        $delegate = new DelegateContainer([]);
        $this->assertFalse($delegate->has('foo'));

        $this->expectException(UnknownDependency::class);
        $this->expectExceptionMessage('No containers provided, can\'t retrieve \'foo\'');
        $delegate->get('foo');
    }
}
