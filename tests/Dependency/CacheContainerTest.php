<?php

// namespace Tests\Dependency;

// use Onion\Framework\Dependency\CacheContainer;
// use PHPUnit\Framework\TestCase;
// use Prophecy\PhpUnit\ProphecyTrait;
// use Psr\Container\ContainerInterface;
// use Sabre\Cache\Memory;
// use stdClass;

// class CacheContainerTest extends TestCase
// {
//     private $cache;
//     private $container;

//     use ProphecyTrait;

//     public function setUp(): void
//     {
//         $this->cache = new Memory;

//         $this->container = $this->prophesize(ContainerInterface::class);
//         $this->container->has('foo')->willReturn(true);
//         $this->container->has('bar')->willReturn(false);
//         $this->container->has('baz')->willReturn(true);
//         $this->container->get(stdClass::class)->will(function () {
//             return new stdClass;
//         });
//         $this->container->has(stdClass::class)->willReturn(true);
//     }

//     public function testSimpleCaching()
//     {
//         $cache = new CacheContainer($this->container->reveal(), $this->cache);
//         $this->assertTrue($cache->has('foo'));
//         $this->assertFalse($cache->has('bar'));
//         $this->assertTrue($cache->has('baz'));
//         $this->assertSame($cache->get(stdClass::class), $cache->get(stdClass::class));
//     }

//     public function testBlacklistKeys()
//     {
//         $cache = new CacheContainer($this->container->reveal(), $this->cache, [
//             stdClass::class
//         ]);

//         $this->assertNotSame($cache->get(stdClass::class), $cache->get(stdClass::class));
//         $this->assertFalse($this->cache->has(stdClass::class));
//     }
// }
