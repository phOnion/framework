<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Router;

use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Http\Middleware\Exceptions\MiddlewareException;
use Onion\Framework\Router\Interfaces\MatcherInterface;
use Onion\Framework\Router\Interfaces\ParserInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Onion\Framework\Router\Factory\RouterFactory;
use Psr\Http\Message\ResponseInterface;

class RouterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
//        $this->container->get(Configuration::class)->willReturn($configuration->reveal());

        $parser = $this->prophesize(ParserInterface::class);
        $this->container->get(ParserInterface::class)
            ->willReturn($parser->reveal());
        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/')->willReturn('/');
        $matcher = $this->prophesize(MatcherInterface::class);
        $matcher->match('/', '/')->willReturn(true);
        $this->container->get(ParserInterface::class)->willReturn($parser->reveal());
        $this->container->get(MatcherInterface::class)->willReturn($matcher->reveal());
        $this->container->has('routes')->willReturn(true);
        $this->container->has(ParserInterface::class)->willReturn(true);
        $this->container->has(MatcherInterface::class)->willReturn(true);
        $this->container->has(ResponseInterface::class)->willReturn(false);
    }

    public function testCreationOfRouterFromTheFactory()
    {
        $this->container->get('routes')->willReturn([
            [
                'name' => 'home',
                'pattern' => '/',
                'middleware' => [
                    \stdClass::class
                ]
            ]
        ]);



        $controller = $this->prophesize(ServerMiddlewareInterface::class)->reveal();
        $this->container->get(\stdClass::class)->willReturn($controller);
        $this->container->has(\stdClass::class)->willReturn(true);



        $factory = new RouterFactory();
        $this->assertInstanceOf(RouterInterface::class, $factory->build($this->container->reveal()));
    }

    public function testExceptionWhenRouteIsInvalid()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->container->get('routes')->willReturn([
            [
                'pattern' => '/'
            ]
        ]);

        $factory = new RouterFactory();

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('A route definition must have a "middleware" key');
        $factory->build($this->container->reveal());
    }
}
