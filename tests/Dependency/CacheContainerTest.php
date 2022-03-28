<?php

namespace Tests\Dependency;

use Onion\Framework\Dependency\CacheContainer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Sabre\Cache\Memory;
use stdClass;

class CacheContainerTest extends TestCase
{
    private ObjectProphecy $cache;
    private ObjectProphecy $container;

    use ProphecyTrait;

    public function setUp(): void
    {
        $this->cache = $this->prophesize(CacheInterface::class);

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->has('foo')->willReturn(true);
        $this->container->has('bar')->willReturn(false);
        $this->container->has('baz')->willReturn(true);
        $this->container->get(stdClass::class)->will(fn () => new stdClass);
        $this->container->has(stdClass::class)->willReturn(true);
    }

    public function testSimpleCaching()
    {
        $this->cache->has('foo')->willReturn(false);
        $this->cache->has('bar')->willReturn(false);
        $this->cache->has('baz')->willReturn(false);
        $this->cache->has(stdClass::class)->shouldBeCalled(2)->willReturn(false, true);
        $this->cache->set(stdClass::class, Argument::type(stdClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cache->get(stdClass::class)
            ->shouldBeCalledOnce()
            ->willReturn(new stdClass);


        $cache = new CacheContainer($this->container->reveal(), $this->cache->reveal());
        $this->assertTrue($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
        $this->assertTrue($cache->has('baz'));
        $this->assertInstanceOf(stdClass::class, $cache->get(stdClass::class));
        $this->assertInstanceOf(stdClass::class, $cache->get(stdClass::class));
    }

    public function testBlacklistKeys()
    {
        $this->cache->has(stdClass::class)->willReturn(false);
        $this->cache->set(stdClass::class, Argument::type(stdClass::class))->shouldNotBeCalled();
        $cache = new CacheContainer($this->container->reveal(), $this->cache->reveal(), [
            stdClass::class
        ]);

        $this->assertNotSame($cache->get(stdClass::class), $cache->get(stdClass::class));
    }
}
