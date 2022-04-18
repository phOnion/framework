<?php

declare(strict_types=1);

namespace Onion\Framework\Http\RequestHandler;

use Psr\Http\Message;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandler implements RequestHandlerInterface
{
    /**
     * @param \Iterator $middleware Middleware of the frame
     */
    public function __construct(private iterable $middleware, private ?Message\ResponseInterface $response = null)
    {
        if (is_array($middleware)) {
            $this->middleware = new \ArrayIterator($middleware);
        }
    }

    public function __clone()
    {
        $this->middleware->rewind();
    }

    /**
     * @param Message\ServerRequestInterface $request
     *
     * @throws \RuntimeException If asked to return the response template, but the template is empty
     * @return Message\ResponseInterface
     */
    public function handle(Message\ServerRequestInterface $request): Message\ResponseInterface
    {
        if ($this->middleware->valid()) {
            $middleware = $this->middleware->current();
            assert($middleware instanceof MiddlewareInterface, new \TypeError('Invalid middleware type'));
            $this->middleware->next();

            return $middleware->process($request, $this);
        }

        if (null === $this->response) {
            throw new \RuntimeException('No base response provided');
        }

        return $this->response;
    }
}
