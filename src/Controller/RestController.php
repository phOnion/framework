<?php
declare(strict_types=1);
namespace Onion\Framework\Controller;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class RestController implements MiddlewareInterface
{
    private const HTTP_METHODS = [
        'get',
        'head',
        'post',
        'put',
        'patch',
        'options',
        'connect',
        'delete',
    ];

    private function getEmptyStream(string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen('php://memory', $mode));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $httpMethod = strtolower($request->getMethod());
        if ($httpMethod === 'head' && !method_exists($this, 'head')) {
            $httpMethod = 'get';
        }

        if (!method_exists($this, $httpMethod)) {
            throw new \BadMethodCallException('Method not implemented');
        }

        /** @var ResponseInterface $response */
        $response = $this->{$httpMethod}($request, $handler);

        if ($httpMethod === 'head') {
            $response = $response->withBody($this->getEmptyStream());
        }

        if ($httpMethod === 'options' && !$response->hasHeader('allow')) {
            $ref = new \ReflectionObject($this);
            $methods = array_intersect(
                self::HTTP_METHODS,
                array_map(function (\ReflectionMethod $method) {
                    return strtolower($method->getName());
                }, $ref->getMethods(\ReflectionMethod::IS_PUBLIC))
            );

            if (empty($methods)) {
                return $response;
            }

            $response = $response->withAddedHeader('allow', implode(', ', $methods));
        }

        return $response;
    }
}
