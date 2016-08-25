<?php
/**
 * PHP Version 5.6.0
 *
 * @category Tests
 * @package  Tests\Router
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Router;

use Onion\Framework\Interfaces\Router\Exception\NotFoundException;
use Onion\Framework\Interfaces\Router\Exception\NotAllowedException;
use Onion\Framework\Interfaces\Router\ParserInterface;
use Onion\Framework\Interfaces\Router\RouteInterface;
use Onion\Framework\Router\Router;
use Psr\Http\Message\UriInterface;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    protected $router;
    protected $route;
    protected $parser;

    protected function setUp()
    {
        $this->router = new Router();
        $route = $this->prophesize(RouteInterface::class);
        $route->setSupportedMethods(['get'])->will(function () use ($route) {
            return $route->reveal();
        });
        $route->setName('home')->will(function () use ($route) {
            return $route->reveal();
        });
        $route->setMiddleware([])->will(function () use ($route) {
            return $route->reveal();
        });
        $route->setPattern('/')->will(function () use ($route) {
            return $route->reveal();
        });
        $route->setParams([])->will(function () use ($route) {
            return $route->reveal();
        });
        $route->getSupportedMethods()->willReturn(['GET']);
        $route->getPattern()->willReturn('/');
        $route->getMiddleware()->willReturn([]);
        $route->getName()->willReturn('home');
//        $route->serialize()->willReturn('cool');
        $this->route = $route->reveal();

        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/')->willReturn('/');
        $parser->match('~^(?:/)$~x', '/')->willReturn([]);

        $this->parser = $parser->reveal();
    }

    public function testExceptionWhenNoRouteRootObjectDefined()
    {
        $this->expectException(\RuntimeException::class);
        $this->router->addRoute(['get'], '/', [], 'home');
    }

    public function testExceptionWhenNoParserDefined()
    {
        $this->expectException(\RuntimeException::class);
        $this->router->setRouteRootObject($this->route);
        $this->router->addRoute(['get'], '/', [], 'home');
    }

    public function testExceptionOnDuplicateRouteName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->setRouteRootObject($this->route);
        $this->router->setParser($this->parser);
        $this->router->addRoute(['get'], '/', [], 'home');
        $this->router->addRoute(['get'], '/', [], 'home');
    }

    public function testExceptionWhenNamedRouteDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->getRouteByName('cool-route');
    }

    public function testExceptionWhenRouteNotFound()
    {
        $this->expectException(NotFoundException::class);
        $this->router->setParser($this->parser);
        $this->router->setRouteRootObject($this->route);
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo/bar');

        $this->assertNull($this->router->match('get', $uri->reveal()));
    }

    public function testExceptionForUnsupportedMethod()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $this->router->setRouteRootObject($this->route);
        $this->router->setParser($this->parser);
        $this->router->addRoute(['get'], '/', []);

        $this->expectException(NotAllowedException::class);
        $this->router->match('POST', $uri->reveal());
    }

    public function testBasicRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $this->router->setRouteRootObject($this->route);
        $this->router->setParser($this->parser);
        $this->router->addRoute(['get'], '/', [], 'home');
        $this->assertEquals('/', $this->router->getRouteByName('home'));
        $this->assertCount(1, $this->router);
    }

    public function testComplexRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $route = $this->prophesize(RouteInterface::class);
        $route->getName()->willReturn(null);
        $route->getPattern()->willReturn('/(?P<foo>.*)');
        $route->getMiddleware()->willReturn([]);
        $route->setPattern('/(?P<foo>.*)')->willReturn(null);
        $route->setMiddleware([])->willReturn(null);
        $route->setParams(['foo' => 'bar'])->willReturn(null);
        $route->getParams()->willReturn(['foo' => 'bar']);
        $route->setSupportedMethods(['get'])->willReturn(null);
        $route->getSupportedMethods()->willReturn(['GET']);

        $this->router->setRouteRootObject($route->reveal());

        $parser = $this->prophesize(ParserInterface::class);
        $parser->match('~^(?:/(?P<foo>.*))$~x', '/bar')->will(function ($args) {
            preg_match($args[0], $args[1], $matches);

            return $matches;
        });
        $parser->parse('/[foo:*]')->willReturn('/(?P<foo>.*)');

        $this->router->setParser($parser->reveal());
        $this->router->addRoute(['get'], '/[foo:*]', []);

        $this->assertInstanceOf(RouteInterface::class, $this->router->match('GET', $uri->reveal()));
    }

    public function testRouteRetrievalByName()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $route = $this->prophesize(RouteInterface::class);
        $route->getName()->willReturn(null);
        $route->getPattern()->willReturn('/(?P<foo>[\w]+)');
        $route->getMiddleware()->willReturn([]);
        $route->setPattern('/(?P<foo>[\w]+)')->willReturn(null);
        $route->setMiddleware([])->willReturn(null);
        $route->setParams(['foo' => 'bar'])->willReturn(null);
        $route->getParams()->willReturn(['foo' => 'bar']);
        $route->setSupportedMethods(['get'])->willReturn(null);
        $route->getSupportedMethods()->willReturn(['GET']);
        $route->serialize()->will(function () use ($route) {
            return $route->reveal();
        });

        $this->router->setRouteRootObject($route->reveal());

        $parser = $this->prophesize(ParserInterface::class);
        $parser->match('~^(?:/(?P<foo>.*))$~x', '/bar')->willReturn(['foo' => 'bar']);
        $parser->parse('/[foo:*]')->willReturn('/(?P<foo>[\w]+)');

        $this->router->setParser($parser->reveal());
        $this->router->addRoute(['get'], '/[foo:*]', [], 'test');

        $this->assertSame('/test', $this->router->getRouteByName('test', ['foo' => 'test']));
        $this->expectException(\InvalidArgumentException::class);
        $this->router->getRouteByName('test', ['bar' => 'foo']);
    }
}
