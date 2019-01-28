<?php
namespace Tests\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Controller\Stub\DummyController;
use Onion\Framework\Controller\RestController;
use GuzzleHttp\Psr7\Response;

class RestControllerTest extends TestCase
{
    private $request;
    private $handler;

    public function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    public function httpMethodProvider()
    {
        return [
            ['get', [], 'get 1'],
            ['head', [], ''],
            ['post', [], 'post 3'],
            ['put', [], 'put 4'],
            ['patch', [], ' patch 5'],
            ['delete', [], 'delete 6'],
            ['options', ['allow' => ['PATCH']], 'options 7'],
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

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method not implemented
     */
    public function testExceptionOnNotImplemented()
    {
        $controller = new class extends RestController {
            public function get() {
                return new Response(200);
            }
        };

        $this->request->getMethod()->willReturn('post');
        $controller->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testGetAndHeadInterop()
    {
        $controller = new class extends RestController {
            public function get() {
                return new Response(200);
            }
        };

        $this->request->getMethod()->willReturn('head');
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

        $this->request->getMethod()->willReturn('head');
        $response = $controller->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testAllowHeaderOptions()
    {
        $this->request->getMethod()->willReturn('options');
        $controller = new DummyController(200, [], 'Options 1 2 3');
        $response = $controller->process(
            $this->request->reveal(),
            $this->handler->reveal()
        );

        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame(
            ['get, head, post, put, patch, options, delete'],
            $response->getHeader('allow')
        );
    }
}
