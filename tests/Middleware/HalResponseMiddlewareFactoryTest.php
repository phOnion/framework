<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Middleware;


use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Middleware\Factory\HalResponseMiddlewareFactory;
use Onion\Framework\Middleware\HalResponseMiddleware;
use Prophecy\Argument;

class HalResponseMiddlewareFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionWhenNoConfigurationIsAvailable()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Configuration::class)->willThrow(UnknownDependency::class);

        $factory = new HalResponseMiddlewareFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No configuration object found in container');

        $factory($container->reveal());
    }

    public function testExceptionWhenNoHALConfigurationIsPresent()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('hal')->willThrow(UnknownDependency::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Configuration::class)->willReturn($config->reveal());

        $factory = new HalResponseMiddlewareFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No configuration about HAL strategies found');

        $factory($container->reveal());
    }

    public function testExceptionWhenThereAreNoStrategiesDefinedInHal()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('hal')->willReturn([]);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Configuration::class)->willReturn($config->reveal());

        $factory = new HalResponseMiddlewareFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HAL Configuration exists, but does not define any strategies');
        $factory($container->reveal());
    }

    public function testContainerExceptionBubbling()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Argument::any())->willThrow(ContainerErrorException::class);

        $factory = new HalResponseMiddlewareFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected error');

        $factory($container->reveal());
    }

    public function testMiddlewareCreation()
    {
        $config = $this->prophesize(Configuration::class);
        $config->get('hal')->willReturn(['strategies' => [\stdClass::class]]);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(Configuration::class)->willReturn($config->reveal());
        $container->get(\stdClass::class)->willReturn(new \stdClass);

        $factory = new HalResponseMiddlewareFactory();
        $this->assertInstanceOf(HalResponseMiddleware::class, $factory($container->reveal()));
    }
}
