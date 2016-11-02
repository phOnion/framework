<?php
declare(strict_types = 1);
namespace Onion\Framework\Http\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\MiddlewareInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message;

class Delegate implements DelegateInterface
{
    /**
     * @var MiddlewareInterface|ServerMiddlewareInterface
     */
    protected $middleware;

    protected $next;

    /**
     * MiddlewareDelegate constructor.
     *
     * @param MiddlewareInterface|ServerMiddlewareInterface $middleware Middleware of the frame
     * @param Delegate                                      $delegate   The next frame
     *
     * @throws Exceptions\MiddlewareException                           if middleware does not implement a
     *                                                                  middleware interface
     */
    public function __construct($middleware, DelegateInterface $delegate = null)
    {
        assert(
            $middleware instanceof MiddlewareInterface || $middleware instanceof ServerMiddlewareInterface,
            new Exceptions\MiddlewareException(
                'Provided middleware must implement either MiddlewareInterface or ServerMiddlewareInterface'
            )
        );

        $this->middleware = $middleware;
        $this->next = $delegate;
    }

    /**
     * @param Message\RequestInterface $request
     *
     * @throws Exceptions\MiddlewareException if returned response is not instance of ResponseInterface
     * @return Message\ResponseInterface
     */
    public function process(Message\RequestInterface $request): Message\ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
        /*
         * Workaround rewriting already written response, possible bug with
         * StreamInterface implementation in diactoros.
         *
         * @ToDo
         */
        if ($response->getBody()->isSeekable() && $response->getBody()->tell() !== $response->getBody()->getSize()) {
            $response->getBody()->seek($response->getBody()->getSize());
        }

        return $response;
    }
}
