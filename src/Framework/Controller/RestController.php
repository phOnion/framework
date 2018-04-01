<?php
declare(strict_types=1);
namespace Onion\Framework\Controller;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;

abstract class RestController implements MiddlewareInterface
{
    private function getEmptyStream(string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen('php://memory', $mode));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $httpMethod = strtolower($request->getMethod());
        try {
            if (!method_exists($this, $httpMethod)) {
                throw new \BadMethodCallException('method not implemented');
            }
            /** @var ResponseInterface $entity */
            $response = $this->{$httpMethod}($request, $handler);

            if ($httpMethod === 'head') {
                $response = $response->withBody($this->getEmptyStream());
            }

            return $response;
        } catch (\BadMethodCallException $ex) {
            return $handler->handle($request)
                ->withStatus(in_array($httpMethod, ['get', 'head'], true) ? 503 : 501)
                ->withBody($this->getEmptyStream());
        }
    }
}
