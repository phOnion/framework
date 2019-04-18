<?php
namespace Tests\Router\Strategy;

use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Strategy\TreeStrategy;
use PHPUnit\Framework\TestCase;

class TreeStrategyTest extends TestCase
{
    public function testSuccessfulResolve()
    {
        $routes = [];
        for ($i=0; $i<20; $i++) {
            $param = "/{$i}/{x}/{arg}?";
            $route = $this->prophesize(RouteInterface::class);
            $route->getPattern()->willReturn($param);
            $route->getName()->willReturn($i);
            $route->hasMethod('GET')->willReturn(true);

            $route->withParameters(['x' => "{$i}"])
                ->willReturn($route->reveal())
                ->shouldBeCalled(1);
            $route->withParameters(['x' => "{$i}", 'arg' => "test{$i}"])
                ->willReturn($route->reveal())
                ->shouldBeCalled(1);

            $routes[] = $route->reveal();

            $strategy = new TreeStrategy($routes);

            $this->assertInstanceOf(RouteInterface::class, $strategy->resolve('GET', "/{$i}/{$i}"));
            $this->assertInstanceOf(RouteInterface::class, $strategy->resolve('GET', "/{$i}/{$i}/test{$i}"));
        }
    }


    public function testUnsuccessfulResolve()
    {
        $strategy = new TreeStrategy([]);
        $this->expectException(NotFoundException::class);

        $strategy->resolve('GET', '/');
    }

    public function testMethodNotAllowed()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->hasMethod('GET')->willReturn(false);
        $route->getMethods()->willReturn(['POST']);
        $route->getPattern()->willReturn('/');
        $route->getName()->willReturn('index');

        $strategy = new TreeStrategy([$route->reveal()]);
        $this->expectException(NotAllowedException::class);
        $strategy->resolve('GET', '/');
    }
}
