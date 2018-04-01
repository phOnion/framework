<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware;

use Psr\Http\Message;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandler implements RequestHandlerInterface
{
    /** @var MiddlewareInterface */
    protected $middleware;

    /** @var Message\ResponseInterface */
    protected $response;

    /**
     * @param MiddlewareInterface[] $middleware Middleware of the frame
     */
    public function __construct(iterable $middleware, Message\ResponseInterface $response = null)
    {
        array_walk($middleware, function ($middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \TypeError(
                    'All members of middleware must implement MiddlewareInterface, ' .
                        gettype($middleware) . ': ' . print_r($middleware, true) . ' given'
                );
            }
        });
        $this->middleware = $middleware;
        $this->response = $response;
    }

    /**
     * @param Message\ServerRequestInterface $request
     *
     * @throws \RuntimeException If asked to return the response template, but the template is empty
     * @return Message\ResponseInterface
     */
    public function handle(Message\ServerRequestInterface $request): Message\ResponseInterface
    {
        if (!empty($this->middleware)) {
            $middleware = array_shift($this->middleware);

            return $middleware->process($request, $this);
        }

        if (null === $this->response) {
            throw new \RuntimeException('No base response provided');
        }

        return $this->response;
    }
}
