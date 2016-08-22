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

use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Psr\Http\Message;

class Stack implements StackInterface
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

        throw new \RuntimeException('No middleware defined, nothing to do');
    }

    public function withMiddleware(MiddlewareInterface $middleware)
    {
        $self = clone $this;
        $self->middleware[] = $middleware;

        return $self;
    }

    public function withoutMiddleware(MiddlewareInterface $middleware)
    {
        if ($this->stack->offsetExists($middleware)) {
            $self = clone $this;
            $self->stack->offsetUnset($middleware);
            return $self;
        }

        throw new \InvalidArgumentException(sprintf(
            'Middleware "%s" not found in current stack, unable to remove',
            get_class($middleware)
        ));
    }
}
