<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Interfaces\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces\Middleware;

use Psr\Http\Message;
/**
 * Interface MiddlewareInterface
 *
 * @package Onion\Framework\Interfaces\Middleware
 */
interface MiddlewareInterface
{
    /**
     * @param Message\RequestInterface $request
     * @param FrameInterface           $frame
     *
     * @return Message\ResponseInterface
     */
    public function process(Message\RequestInterface $request, FrameInterface $frame = null);
}
