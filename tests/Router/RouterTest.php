<?php
namespace Tests\Router;

use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Router\Router;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Http\Message\UriInterface;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    protected $router;
    protected $parser;
    protected $matcher;
    protected $delegate;

    protected function setUp()
    {
        $matcher = $this->prophesize(MatcherInterface::class);
        $matcher->match(new AnyValueToken(), new AnyValueToken())->will(function ($args) {
            preg_match('~^' . $args[0] . '$~x', $args[1], $matches);

            return $matches;
        });

        $this->router = new Router(
            $matcher->reveal()
        );

        $this->delegate = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $this->matcher = $matcher->reveal();
    }

    public function testTraverasble()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getName()->willReturn('test');
        $route->getPattern()->willReturn('/');
        $this->router = $this->router->addRoute($route->reveal());
        $this->assertInstanceOf(
            RouteInterface::class,
            $this->router->getIterator()->current()
        );
    }

    public function testExceptionWhenNoRouteRootObjectDefined()
    {
        $this->expectException(\TypeError::class);
        $this->router->addRoute('/', null, ['get'], 'home');
    }

    public function testExceptionOnDuplicateRouteName()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->expectException(\InvalidArgumentException::class);
        $route = $this->prophesize(RouteInterface::class);
        $route->getName()->willReturn('home');
        $route->getPattern()->willReturn('/');
        $this->router = $this->router->addRoute($route->reveal());
        $this->router = $this->router->addRoute($route->reveal());
    }

    public function testExceptionWhenNamedRouteDoesNotExist()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->router->getRouteByName('cool-route');
    }

    public function testExceptionWhenRouteNotFound()
    {
        $this->expectException(NotFoundException::class);
        $router = new Router($this->matcher);
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo/bar');

        $this->assertNull($router->match('get', $uri->reveal()));
    }

    public function testExceptionForUnsupportedMethod()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $route = $this->prophesize(RouteInterface::class);
        $route->getPattern()->willReturn('/');
        $route->getName()->willReturn('foo');
        $route->getMethods()->willReturn(['GET']);
        $this->router = $this->router->addRoute($route->reveal());

        $this->expectException(NotAllowedException::class);
        try {
            $this->router->match('POST', $uri->reveal());
        } catch (MethodNotAllowedException $ex) {
            $this->assertSame(['GET'], $ex->getAllowedMethods());
            throw $ex;
        }
    }

    public function testBasicRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $route = $this->prophesize(RouteInterface::class);
        $route->getPattern()->willReturn('/');
        $route->getName()->willReturn('home');
        $this->router = $this->router->addRoute($route->reveal());
        $this->assertEquals('/', $this->router->getRouteByName('home'));
        $this->assertCount(1, $this->router);
    }

    public function testComplexRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $router = new Router($this->matcher);
        $route = $this->prophesize(RouteInterface::class);
        $route->getPattern()->willReturn('/(?P<foo>.*)');
        $route->getName()->willReturn('foo');
        $route->getMethods()->willReturn(['GET']);
        $route->hydrate(['parameters' => ['foo' => 'bar']])->willReturn($route->reveal());
        $router = $router->addRoute($route->reveal());

        $this->assertInstanceOf(
            RouteInterface::class,
            $router->match('GET', $uri->reveal())
        );
    }

    public function testRouteRetrievalByName()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $matcher = $this->prophesize(MatcherInterface::class);
        $matcher->match('~^(?:/(?P<foo>.*))$~x', '/bar')->willReturn(['foo' => 'bar']);

        $router = new Router($matcher->reveal());
        $route = $this->prophesize(RouteInterface::class);
        $route->getName()->willReturn('test');
        $route->getPattern()->willReturn('/(?P<foo>.*)');
        $router = $router->addRoute($route->reveal());

        $this->assertSame('/test', $router->getRouteByName('test', ['foo' => 'test']));
        $this->expectException(\InvalidArgumentException::class);
        $router->getRouteByName('test', ['bar' => 'foo']);
    }
}
