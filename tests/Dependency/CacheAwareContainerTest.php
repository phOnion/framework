<?php
namespace tests\Dependency;

use Onion\Framework\Dependency\CacheAwareContainer;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as Container;
use Psr\SimpleCache\CacheInterface;

class CacheAwareContainerTest extends \PHPUnit\Framework\TestCase
{
    private $cache;
    private $factory;
    public function setUp()
    {
        $this->cache = $this->prophesize(CacheInterface::class);
        $this->factory = new class implements FactoryInterface
        {
            public function build(Container $container)
            {
                return new \Onion\Framework\Dependency\Container([
                    'bar' => 'baz'
                ]);
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
        $this->cache->set('bar', 'baz')->willReturn(true);

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
}
