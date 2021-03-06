<?php
namespace Tests\Router\Strategy;

use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Strategy\CompiledRegexStrategy;
use PHPUnit\Framework\TestCase;

class CompiledRegexStrategyTest extends TestCase
{
    public function testSimpleResolve()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPattern()->willReturn('/foo/bar/{arg}');
        $route->getName()->willReturn('/foo/bar/{arg}');
        $route->hasMethod('GET')->willReturn(true);

        $route->withParameters(['arg' => "baz"])
            ->willReturn($route->reveal())
            ->shouldBeCalledOnce();
        $strategy = new CompiledRegexStrategy([
            $route->reveal()
        ], 5);

        $this->assertInstanceOf(
            RouteInterface::class,
            $strategy->resolve('GET', '/foo/bar/baz')
        );
    }

    public function testSuccessfulResolve()
    {
        $routes = [];
        foreach (range(1, 10) as $count) {
            $routes = [];
            for ($i=0; $i<11; $i++) {
                $param = "/{$i}/{x}/{arg}?";
                $route = $this->prophesize(RouteInterface::class);
                $route->getPattern()->willReturn($param);
                $route->getName()->willReturn($i);
                $route->hasMethod('GET')->willReturn(true);

                $route->withParameters(['x' => "{$i}"])
                    ->willReturn($route->reveal())
                    ->shouldBeCalledOnce();
                $route->withParameters(['x' => "{$i}", 'arg' => "test{$i}"])
                    ->willReturn($route->reveal())
                    ->shouldBeCalledOnce();

                $routes[] = $route->reveal();

                $strategy = new CompiledRegexStrategy($routes, $count);

                $this->assertInstanceOf(RouteInterface::class, $strategy->resolve('GET', "/{$i}/{$i}/test{$i}"));
                $this->assertInstanceOf(RouteInterface::class, $strategy->resolve('GET', "/{$i}/{$i}"));
            }
        }
    }

    public function testUnsuccessfulResolve()
    {
        $strategy = new CompiledRegexStrategy([], 5);
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

        $strategy = new CompiledRegexStrategy([$route->reveal()], 10);
        $this->expectException(NotAllowedException::class);
        $strategy->resolve('GET', '/');
    }
}
