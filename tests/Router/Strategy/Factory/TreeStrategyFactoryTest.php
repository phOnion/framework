<?php

namespace Tests\Router\Strategy\Factory;

use Onion\Framework\Router\Strategy\Factory\TreeStrategyFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class TreeStrategyFactoryTest extends TestCase
{
    /** @var TreeStrategyFactory $factory */
    private $factory;

    use ProphecyTrait;

    public function setUp(): void
    {
        $this->factory = new TreeStrategyFactory();
    }

    public function testBadResolver()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('router.groups')
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $container->has('router.resolver')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $container->get('router.resolver')
            ->willReturn('foo')
            ->shouldBeCalledOnce();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provided 'foo' does not exist.");

        $this->factory->build($container->reveal());
    }

    public function testBasicResolution()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('router.groups')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $container->get('router.groups')
            ->willReturn(['foo' => ['prefix' => '/foo']])
            ->shouldBeCalledOnce();
        $container->has('router.resolver')
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $container->has('router.count')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $container->get('router.count')
            ->willReturn(10)
            ->shouldBeCalledOnce();
        $container->has(ResponseInterface::class)
            ->willReturn(true)
            ->shouldBeCalledTimes(3);
        $container->get(ResponseInterface::class)
            ->willReturn($this->prophesize(ResponseInterface::class)->reveal())
            ->shouldBeCalledTimes(3);

        $container->get('routes')
            ->willReturn([
                [
                    'pattern' => '/',
                    'middleware' => [
                        'foo',
                    ],
                ], [
                    'pattern' => '/{name}',
                    'middleware' => [
                        'bar',
                    ],
                ], [
                    'pattern' => '/products/{id}?',
                    'middleware' => [
                        'baz',
                    ]
                ], [
                    'pattern' => '/bar',
                    'middleware' => [
                        'foobar',
                    ],
                ],
            ])->shouldBeCalledOnce();
        $this->factory->build($container->reveal());
    }
}
