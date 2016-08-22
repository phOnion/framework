<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Http
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Interfaces\Application;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\ClientMiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message;

class Frame implements FrameInterface
{
    /**
     * @var ClientMiddlewareInterface|ServerMiddlewareInterface
     */
    protected $middleware;

    protected $frame;

    /**
     * MiddlewareDelegate constructor.
     *
     * @param MiddlewareInterface|null $middleware
     * @param Frame                    $frame
     */
    public function __construct(MiddlewareInterface $middleware, Frame $frame = null)
    {
        $this->middleware = $middleware;
        $this->frame = $frame;
    }

    /**
     * @param Message\RequestInterface $request
     *
     * @throws \RuntimeException if returned response is not instance of ResponseInterface
     * @return Message\ResponseInterface
     */
    public function next(Message\RequestInterface $request)
    {
        $response = $this->middleware->handle($request, $this->frame);
        if (!$response instanceof Message\ResponseInterface) {
            throw new \RuntimeException(sprintf(
                'Middleware "%s" does not return a response. Response type is: %s',
                get_class($this->middleware),
                (gettype($response) === 'object') ?
                    get_class($response) : gettype($response)
            ));
        }

        /*
         * Workaround rewriting already written response, possible bug with
         * StreamInterface implementation in diactoros.
         */
        if ($response->getBody()->isSeekable() && $response->getBody()->tell() !== $response->getBody()->getSize()) {
            $response->getBody()->seek($response->getBody()->getSize());
        }

        return $response;
    }
}
