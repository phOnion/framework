<?php
namespace tests\Dependency;

use Onion\Framework\Dependency\CacheAwareContainer;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface as Container;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheAwareContainerTest extends \PHPUnit\Framework\TestCase
{
    private $cache;
    private $factory;
    public function setUp()
    {
        $this->cache = $this->prophesize(CacheInterface::class);
        $mock = $this->prophesize(ContainerInterface::class);
        $mock->get('bar')->willReturn('baz');
        $mock->has('bar')->willReturn(true);
        $mock->has('test')->willReturn(false);

        $this->factory = new class ($mock->reveal()) implements FactoryInterface
        {
            private $container;
            public function __construct($mock)
            {
                $this->container = $mock;
            }
            public function build(Container $container)
            {
                return $this->container;
            }
        };

        $this->fakeFactory = new class implements FactoryInterface
        {
            public function build(Container $container)
            {
                return 42;
            }
        };
    }

    public function testCacheNotHavingKey()
    {
        $this->cache->has('test')->willReturn(false);
        $cacheContainer = new CacheAwareContainer(
            $this->factory,
            $this->cache->reveal()
        );

        $this->assertFalse($cacheContainer->has('test'));
    }

    public function testCacheHavingKey()
    {
        $this->cache->has('test')->willReturn(true);
        $cacheContainer = new CacheAwareContainer(
            $this->factory,
            $this->cache->reveal()
        );

        $this->assertTrue($cacheContainer->has('test'));
    }

    public function testCacheRetrievalOfKey()
    {
        $this->cache->has('test')->willReturn(false);
        $this->cache->has('foo')->willReturn(true);
        $this->cache->get('foo')->willReturn('bar');

        $cacheContainer = new CacheAwareContainer(
            $this->factory,
            $this->cache->reveal()
        );

        $this->assertFalse($cacheContainer->has('test'));
        $this->assertTrue($cacheContainer->has('foo'));
        $this->assertSame($cacheContainer->get('foo'), 'bar');

    }

    public function testStoringValueInCache()
    {
        $this->cache->has('bar')->willReturn(false);
        $this->cache->set('bar', 'baz')->willReturn(true)
            ->shouldBeCalledOnce();

        $cacheContainer = new CacheAwareContainer(
            $this->factory,
            $this->cache->reveal()
        );
        $this->assertTrue($cacheContainer->has('bar'));
        $this->assertSame($cacheContainer->get('bar'), 'baz');
    }

    public function testHonoringOfBlacklistedKeys()
    {
        $this->cache->set('bar', 'baz')->willThrow(new \LogicException('Should not be called'));
        $this->cache->has('bar')->willReturn(false);
        $container = new CacheAwareContainer($this->factory, $this->cache->reveal(), [
            'bar'
        ]);
        $this->assertSame($container->get('bar'), 'baz');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid factory result
     */
    public function testInvalidContainerResult()
    {
        $this->cache->has('foo')->willReturn(false);
        $container = new CacheAwareContainer($this->fakeFactory, $this->cache->reveal());
        $container->get('foo');
    }
}
