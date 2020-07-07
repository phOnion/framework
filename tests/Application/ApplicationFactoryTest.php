<?php
namespace Tests\Application;

use Onion\Framework\Application;
use Onion\Framework\Application\Factory\ApplicationFactory;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Prophecy\Argument\Token\AnyValueToken;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

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

        $requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $requestHandler->handle(new AnyValueToken)->willReturn($this->prophesize(ResponseInterface::class)->reveal());
        $container->get(RequestHandlerInterface::class)->willReturn($requestHandler->reveal());

        $factory = new ApplicationFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);

        $app = $factory->build($container->reveal());
        $this->assertInstanceOf(Application\Application::class, $app);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('GET');
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/');
        $request->getUri()->willReturn($uri->reveal());
        $response = $app->run($request->reveal());
    }
}
