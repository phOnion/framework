<?php
namespace Tests\Application;

use Onion\Framework\Application;
use Onion\Framework\Application\Factory\ApplicationFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Tests\Application\Stubs\MiddlewareStub;

class ApplicationFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testApplicationContainerRetrieval()
    {
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

        $container->has('application.authorization.base')->willReturn(false);
        $container->has('application.authorization.proxy')->willReturn(false);
        $container->has(\Psr\Log\LoggerInterface::class)->willReturn(false);
        $container->get('test')->willReturn(new MiddlewareStub());

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);

        $app = $factory->build($container->reveal());
        $this->assertInstanceOf(Application\Application::class, $app);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('GET');
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $request->getUri()->willReturn($uri->reveal());
        $response = $app->handle($request->reveal());
        $this->assertSame(500, $response->getStatusCode());
    }
}
