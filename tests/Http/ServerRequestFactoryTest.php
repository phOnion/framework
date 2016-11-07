<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Http\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryInit()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $factory = new ServerRequestFactory();

        $this->assertInstanceOf(ServerRequestInterface::class, $factory->build($container->reveal()));
    }
}
