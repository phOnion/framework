<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Http\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Http\Middleware;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Psr\Http\Message;

class ClosureMiddleware implements MiddlewareInterface
{
    /**
     * @var \Closure
     */
    protected $callable;
    public function __construct(\Closure $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param Message\RequestInterface $request
     * @param FrameInterface           $frame
     *
     * @return Message\ResponseInterface
     */
    public function handle(Message\RequestInterface $request, FrameInterface $frame = null)
    {
        return call_user_func($this->callable, $request, $frame);
    }
}
