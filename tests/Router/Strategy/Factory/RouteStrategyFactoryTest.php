<?php
namespace Tests\Router\Strategy\Factory;

use Onion\Framework\Router\Interfaces\ResolverInterface;
use Onion\Framework\Router\Strategy\Factory\RouteStrategyFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteStrategyFactoryTest extends TestCase
{
    /** @var RouteStrategyFactory $factory */
    private $factory;
    public function setUp(): void
    {
        $this->factory = new RouteStrategyFactory();
    }

    public function testBadResolver()
    {
        $container = $this->prophesize(ContainerInterface::class);
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
                ],
            ])->shouldBeCalledOnce();
        $this->factory->build($container->reveal());
    }
}
