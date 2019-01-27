<?php
namespace Tests\Controller\Stub;

use Onion\Framework\Controller\RestController;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

class DummyController extends RestController
{
    private $statusCode;
    private $headers = [];
    private $body;

    public function __construct(int $statusCode, $headers, $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function get(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function delete(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function post(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function put(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function patch(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function options(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }

    public function head(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        return new Response($this->statusCode, $this->headers, "{$this->body}");
    }
}
