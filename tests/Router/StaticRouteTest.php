<?php declare(strict_types=1);
namespace Tests\Router;

use Onion\Framework\Router\StaticRoute;

class StaticRouteTest extends \PHPUnit\Framework\TestCase
{
    private $route;
    public function setUp()
    {
        $this->route = new StaticRoute('/');
    }

    public function testMatch()
    {
        $this->assertSame($this->route->getName(), '/');
        $this->assertSame($this->route->getPattern(), '/');
        $this->assertTrue($this->route->isMatch('/'));
        $this->assertEmpty($this->route->getParameters());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No handler provided
     */
    public function testEmptyRequestHandler()
    {
        $this->route->getRequestHandler();
    }

    public function testNamedRoute()
    {
        $route = new StaticRoute('/', 'home');
        $this->assertSame('home', $route->getName());
    }
}
