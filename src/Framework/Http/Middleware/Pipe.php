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

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Psr\Http\Message;

class Pipe implements StackInterface
{
    /**
     * @var \SplStack
     */
    protected $stack;

    protected $middleware = [];

    public function __construct()
    {
        $this->stack = new \SplStack();
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
        foreach ($this->middleware as $middleware) {
            $this->stack[] = new Frame(
                $middleware,
                $this->stack->isEmpty() ?
                    null : $this->stack->top()
            );
        }

        if (!$this->stack->isEmpty()) {
            return $this->stack->top()->next($request);
        }

        if ($frame !== null) {
            return $frame->next($request);
        }

        throw new \RuntimeException('No middleware defined, nothing to do');
    }
}
