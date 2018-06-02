<?php
namespace Tests\Application;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Application;
use Onion\Framework\Application\Factory\ApplicationFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Tests\Application\Stubs\MiddlewareStub;

class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationContainerRetrieval()
    {
        $stack = $this->prophesize(RequestHandlerInterface::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('routes')->willReturn([
            [
                'name' => 'home',
                'pattern' => '/',
                'middleware' => [
                    'test'
                ]
            ]
        ]);
        $container->has(RequestHandlerInterface::class)->willReturn(false);
        $container->has('application.authorization.base')->willReturn(false);
        $container->has('application.authorization.proxy')->willReturn(false);
        $container->has(\Psr\Log\LoggerInterface::class)->willReturn(false);
        $container->get('test')->willReturn(new MiddlewareStub());

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->assertInstanceOf(Application\Application::class, $factory->build($container->reveal()));
    }
}
