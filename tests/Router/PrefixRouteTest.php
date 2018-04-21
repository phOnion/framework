<?php declare(strict_types=1);
namespace Tests\Router;

use Onion\Framework\Router\PrefixRoute;

class PrefixRouteTest extends \PHPUnit_Framework_TestCase
{
    private $route;
    public function setUp()
    {
        $this->route = new PrefixRoute('/foo');
    }

    public function testMatch()
    {
        $this->assertSame($this->route->getName(), '/foo/*');
        $this->assertSame($this->route->getPattern(), '/foo/*');
        $this->assertTrue($this->route->isMatch('/foo/test'));
    }
}
