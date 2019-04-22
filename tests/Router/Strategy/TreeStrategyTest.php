<?php
namespace Tests\Router\Strategy;

use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Strategy\TreeStrategy;
use PHPUnit\Framework\TestCase;

class TreeStrategyTest extends TestCase
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
        $strategy = new TreeStrategy([
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
        for ($i=0; $i<20; $i++) {
            $param = "/{$i}/{x:\d+}/{arg:test{$i}}";
            $route = $this->prophesize(RouteInterface::class);
            $route->getPattern()->willReturn($param);
            $route->getName()->willReturn($i);
            $route->hasMethod('GET')->willReturn(true);

            $route->withParameters(['x' => "{$i}", 'arg' => "test{$i}"])
                ->willReturn($route->reveal())
                ->shouldBeCalledOnce();

            $routes[] = $route->reveal();

            $strategy = new TreeStrategy($routes);

            $this->assertInstanceOf(RouteInterface::class, $strategy->resolve('GET', "/{$i}/{$i}/test{$i}"));
        }
    }


    public function testUnsuccessfulResolve()
    {
        $strategy = new TreeStrategy([]);
        $this->expectException(NotFoundException::class);

        $strategy->resolve('GET', '/');
    }

    public function testSuccessfulComplexResolve()
    {
        $route = $this->prophesize(RouteInterface::class);
            $route->getPattern()->willReturn(
                '/test/{arg1:foo}/simple/{example:yes}/mate/{name:Baz}'
            );
            $route->getName()->willReturn('test');
            $route->hasMethod('GET')->willReturn(true);

            $route->withParameters([
                'arg1' => "foo",
                'example' => "yes",
                'name' => 'Baz',
            ])->willReturn($route->reveal())
                ->shouldBeCalledOnce();

            $routes[] = $route->reveal();

        $strategy = new TreeStrategy($routes);
        $this->assertInstanceOf(
            RouteInterface::class,
            $strategy->resolve('GET', '/test/foo/simple/yes/mate/Baz')
        );
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

    public function testWildcardMatches()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->hasMethod('GET')->willReturn(true);
        $route->getMethods()->willReturn(['GET']);
        $route->getPattern()->willReturn('/test/{name}/*');
        $route->getName()->willReturn('index');

        $route->withParameters([
            'name' => 'foo',
        ])->willReturn($route->reveal())
            ->shouldBeCalledOnce();

        $strategy = new TreeStrategy([$route->reveal()]);
        $this->assertInstanceOf(
            RouteInterface::class,
            $strategy->resolve('GET', '/test/foo/bar')
        );
    }
}
