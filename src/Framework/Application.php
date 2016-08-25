<?php
/**
 * PHP Version 5.6.0
 *
 * @category Kernel
 * @package  Onion\Framework
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use \Psr\Http\Message;
use Zend\Diactoros\Response\EmitterInterface;

class Application implements MiddlewareInterface
{
    /**
     * @var StackInterface
     */
    protected $stack;

    /**
     * @var EmitterInterface
     */
    protected $emitter;

    public function __construct(StackInterface $stack, EmitterInterface $emitter)
    {
        $this->stack = $stack;
        $this->emitter = $emitter;
    }

    public function run(Message\RequestInterface $request)
    {
        ob_start();
        $response = $this->process($request, null);
        ob_end_clean();

        return $this->emitter->emit($response);
    }

    /**
     * @param Message\RequestInterface $request
     * @param FrameInterface           $frame
     *
     * @return Message\ResponseInterface
     */
    public function process(Message\RequestInterface $request, FrameInterface $frame = null)
    {
        try {
            return $this->stack->handle($request);
        } catch (\Exception $ex) {
            if ($frame !== null) {
                return $frame->next($request);
            }
        }

        return null;
    }
}
