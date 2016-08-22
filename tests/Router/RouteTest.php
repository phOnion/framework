<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Router;


use Onion\Framework\Router\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Route
     */
    protected $route;

    public function setUp()
    {
        $this->route = new Route();
    }

    public function testStaticValuesRetrieval()
    {
        $this->route->setName('home');
        $this->route->setMethods(['get']);
        $this->route->setCallable([]);
        $this->route->setPattern('/');
        $this->route->setParams(['foo' => 'bar']);

        $this->assertSame('home', $this->route->getName());
        $this->assertContainsOnlyInstancesOf(\Closure::class, $this->route->getCallable());
        $this->assertSame('/', $this->route->getPattern());
        $this->assertCount(1, $this->route->getSupportedMethods());
        $this->assertContains('GET', $this->route->getSupportedMethods());
        $this->assertSame(['foo' => 'bar'], $this->route->getParams());
        $this->assertJsonStringEqualsJsonString(
            '{"name": "home", "handler": [], "methods": ["GET"], "pattern": "/"}',
            json_encode($this->route)
        );
        $this->route->setParams([]);
        $this->assertEquals($this->route, unserialize(serialize($this->route)));
    }
}
