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
    /**
     * @var \SplFixedArray
     */
    protected $middleware;

    /**
     * Pipe constructor.
     *
     * @param MiddlewareInterface[]|ServerMiddlewareInterface[] $middleware
     *
     * @throws \InvalidArgumentException if for some magical reason self::initialize fails
     */
    public function __construct(array $middleware = [])
    {
        $this->initialize($middleware);
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
            try {
                $this->middleware->rewind();
                while ($this->middleware->valid()) {
                    $stack = new Frame($this->middleware->current(), $stack);
                    $this->middleware->next();
                }

                return $stack->next($request);
            } catch (\InvalidArgumentException $ex) {
                if ($frame !== null) {
                    return $frame->next($request);
                }
            }
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

        /*
         * So the last will be first, and the first last.
         *
         * This will flip the array and make it LIFO and will
         * preserve the definition logic, namely 1st defined will
         * be the last called.
         */
        $this->middleware = \SplFixedArray::fromArray(array_reverse($args[0]));
    }
}
