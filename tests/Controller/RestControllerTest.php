<?php
namespace Tests\Controller;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Controller\RestController;
use Onion\Framework\Router\Interfaces\RouteInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Controller\Stub\DummyController;

class RestControllerTest extends TestCase
{
    private $request;
    private $handler;

    public function setUp(): void
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    public function httpMethodProvider()
    {
        return [
            ['GET', [], 'get 1'],
            ['HEAD', [], ''],
            ['POST', [], 'post 3'],
            ['PUT', [], 'put 4'],
            ['PATCH', [], ' patch 5'],
            ['DELETE', [], 'delete 6'],
            ['OPTIONS', ['allow' => ['PATCH']], 'options 7'],
            ['CUSTOM', [], 'happy-custom-method'],
        ];
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testSimpleInvoke($method, $headers, $body)
    {
        $controller = new DummyController(200, $headers, $body);
        $this->request->getMethod()->willReturn($method);

        $response = $controller->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame($headers, $response->getHeaders());
    }

    public function testExceptionOnNotImplemented()
    {
        $controller = new class extends RestController {
            public function get() {
                return new Response(200);
            }
        };
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method not implemented');

        $this->request->getMethod()->willReturn('post');
        $controller->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testInterchangeableGetAndHead()
    {
        $controller = new class extends RestController {
            public function get() {
                return new Response(200);
            }
        };

        $this->request->getMethod()->willReturn('HEAD');
        $response = $controller->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetAndHeadInterference()
    {
        $controller = new class extends RestController {
            public function get() {
                return new Response(200);
            }

            public function head() {
                return new Response(204);
            }
        };

        $this->request->getMethod()->willReturn('HEAD');
        $response = $controller->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testAllowHeaderOptions()
    {
        $this->request->getMethod()->willReturn('OPTIONS');

        $route = $this->prophesize(RouteInterface::class);
        $route->getMethods()->willReturn([
            'get', 'head', 'post', 'put', 'patch', 'options', 'delete', 'custom'
        ])->shouldBeCalledOnce();

        $this->request->getAttribute('route')->willReturn($route->reveal());
        $controller = new DummyController(200, [], 'Options 1 2 3');
        $response = $controller->process(
            $this->request->reveal(),
            $this->handler->reveal()
        );

        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame(
            ['get, head, post, put, patch, options, delete, custom'],
            $response->getHeader('allow')
        );
    }
}
