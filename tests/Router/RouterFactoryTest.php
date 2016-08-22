<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Router;


use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces\MiddlewareInterface;
use Onion\Framework\Interfaces\Router\ParserInterface;
use Onion\Framework\Interfaces\Router\RouteInterface;
use Onion\Framework\Interfaces\Router\RouterInterface;
use Onion\Framework\Router\Factory\RouterFactory;
use Onion\Framework\Router\Route;

class RouterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    protected  function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
//        $this->container->get(Configuration::class)->willReturn($configuration->reveal());

        $parser = $this->prophesize(ParserInterface::class);
        $this->container->get(ParserInterface::class)
            ->willReturn($parser->reveal());
        $this->container->get(RouteInterface::class)
            ->willReturn($this->prophesize(RouteInterface::class)->reveal());
    }

    public function testCreationOfRouterFromTheFactory()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('routes')->willReturn([
            [
                'name' => 'home',
                'pattern' => '/',
                'middleware' => [
                    \stdClass::class
                ]
            ]
        ]);
        $this->container->get(Configuration::class)->willReturn($config->reveal());

        $controller = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->container->get(\stdClass::class)->willReturn($controller);
        $this->container->has(\stdClass::class)->willReturn(true);


        $factory = new RouterFactory();
        $this->assertInstanceOf(RouterInterface::class, $factory($this->container->reveal()));
    }

    public function testExceptionWhenRouteIsInvalid()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('routes')->willReturn([
            [
                'pattern' => '/'
            ]
        ]);
        $this->container->get(Configuration::class)->willReturn($config->reveal());

        $factory = new RouterFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Every route definition must have "pattern" and "middleware" entry');
        $factory($this->container->reveal());
    }

    public function testExceptionWhenMiddlewareEntryIsNotRegisteredWithContainer()
    {
        $this->container->has(\stdClass::class)->willReturn(false);
        $config = $this->prophesize(Configuration::class);
        $config->get('routes')->willReturn([
            [
                'pattern' => '/',
                'middleware' => [
                    \stdClass::class
                ]
            ]
        ]);
        $this->container->get(Configuration::class)->willReturn($config->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware "stdClass" is not registered in the container');
        $factory = new RouterFactory();
        $factory($this->container->reveal());
    }
}
