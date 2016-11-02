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

use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\ParserInterface;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Router;
use Prophecy\Argument;
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
        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/')->willReturn('/');
        $matcher = $this->prophesize(MatcherInterface::class);
        $matcher->match(Argument::any(), Argument::any())->will(function ($args) {
            preg_match('~^' . $args[0] . '$~x', $args[1], $matches);

            return $matches;
        });

        $this->router = new Router(
            $parser->reveal(),
            $matcher->reveal()
        );



        $this->delegate = $this->prophesize(DelegateInterface::class)->reveal();

        $this->parser = $parser->reveal();
        $this->matcher = $matcher->reveal();
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
        $this->router->addRoute('/', $this->delegate, ['get'], 'home');
        $this->router->addRoute('/', $this->delegate, ['get'], 'home');
    }

    public function testExceptionWhenNamedRouteDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->getRouteByName('cool-route');
    }

    public function testExceptionWhenRouteNotFound()
    {
        $this->expectException(NotFoundException::class);
        $router = new Router($this->parser, $this->matcher);
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo/bar');

        $this->assertNull($router->match('get', $uri->reveal()));
    }

    public function testExceptionForUnsupportedMethod()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $this->router->addRoute('/', $this->delegate, ['get']);

        $this->expectException(NotAllowedException::class);
        $this->router->match('POST', $uri->reveal());
    }

    public function testBasicRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $this->router->addRoute('/', $this->delegate, ['get'], 'home');
        $this->assertEquals('/', $this->router->getRouteByName('home'));
        $this->assertCount(1, $this->router);
    }

    public function testComplexRouteCreation()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/[foo:*]')->willReturn('/(?P<foo>.*)');

        $router = new Router($parser->reveal(), $this->matcher);
        $router->addRoute('/[foo:*]', $this->delegate, ['GET']);

        $this->assertSame([
            '/(?P<foo>.*)',
            $this->delegate,
            ['GET'],
            [0 => '/bar', 'foo' => 'bar', 1 => 'bar']
        ], $router->match('GET', $uri->reveal()));
    }

    public function testRouteRetrievalByName()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/bar');

        $parser = $this->prophesize(ParserInterface::class);
        $matcher = $this->prophesize(MatcherInterface::class);
        $matcher->match('~^(?:/(?P<foo>.*))$~x', '/bar')->willReturn(['foo' => 'bar']);
        $parser->parse('/[foo:*]')->willReturn('/(?P<foo>[\w]+)');

        $router = new Router($parser->reveal(), $matcher->reveal());
        $router->addRoute('/[foo:*]', $this->delegate, ['get'], 'test');

        $this->assertSame('/test', $router->getRouteByName('test', ['foo' => 'test']));
        $this->expectException(\InvalidArgumentException::class);
        $router->getRouteByName('test', ['bar' => 'foo']);
    }
}
