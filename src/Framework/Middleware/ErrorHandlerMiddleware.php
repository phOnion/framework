<?php
/**
 * PHP Version 5.6.0
 *
 * @category Middleware
 * @package  Onion\Framework\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Psr\Http\Message;

class ErrorHandlerMiddleware implements ServerMiddlewareInterface
{
    /**
     * @var StackInterface
     */
    protected $middleware;

    public function __construct(StackInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    public function handle(Message\ServerRequestInterface $request, FrameInterface $frame = null)
    {
        if ($request->getAttribute('exception', false) !== false) {
            return $this->middleware->handle($request);
        }

        return $frame->next($request);
    }
}
