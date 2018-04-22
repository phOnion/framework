<?php
declare(strict_types=1);
namespace Onion\Framework\Controller;

use GuzzleHttp\Psr7\Stream;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class RestController implements MiddlewareInterface
{
    private function getEmptyStream(string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen('php://memory', $mode));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $httpMethod = strtolower($request->getMethod());
        if ($httpMethod === 'head' && !method_exists('head')) {
            $httpMethod = 'get';
        }

        if (!method_exists($this, $httpMethod)) {
            throw new \BadMethodCallException('method not implemented');
        }

        /** @var ResponseInterface $entity */
        $response = $this->{$httpMethod}($request, $handler);

        if ($httpMethod === 'head') {
            $response = $response->withBody($this->getEmptyStream());
        }

        return $response;
    }
}
