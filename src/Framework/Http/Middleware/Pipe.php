<?php
/**
 * PHP Version 5.6.0
 *
 * @category Middleware
 * @package  Onion\Framework\Http
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Interfaces\Common\PrototypeObjectInterface;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Psr\Http\Message;

class Pipe implements StackInterface, PrototypeObjectInterface
{
    protected $middleware = [];

    /**
     * Pipe constructor.
     *
     * @param MiddlewareInterface[]|ServerMiddlewareInterface[] $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function handle(Message\RequestInterface $request)
    {
        return $this->process($request, null);
    }

    /**
     * @param Message\RequestInterface $request
     * @param FrameInterface           $frame
     *
     * @return Message\ResponseInterface
     * @throws \RuntimeException
     */
    public function process(Message\RequestInterface $request, FrameInterface $frame = null)
    {
        $stack = null;
        if (0 !== count($this->middleware)) {
            $iterator = new \ArrayIterator(array_reverse($this->middleware));
            $iterator->rewind();
            while ($iterator->valid()) {
                $stack = new Frame($iterator->current(), $stack);
                $iterator->next();
            }

            return $stack->next($request);
        }

        if ($frame !== null) {
            return $frame->next($request);
        }

        throw new \RuntimeException('No middleware defined, nothing to do');
    }

    /**
     * @param array $args A list of middleware to register with the pipe
     *
     * @throws \InvalidArgumentException If the required argument is not passed in the constructor
     *
     * @return void
     */
    public function initialize(...$args)
    {
        if (0 === count($args) || !is_array($args[0])) {
            throw new \InvalidArgumentException(
                'An array with middleware must be passed as argument to successfully initialize the Pipe'
            );
        }

        $this->middleware = $args[0];
    }
}
