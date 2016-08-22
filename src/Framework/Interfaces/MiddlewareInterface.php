<?php
/**
 * PHP Version 5.6.0
 *
 * @category Middleware
 * @package  Onion\Framework\Interfaces
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces;

use Psr\Http\Message;

interface MiddlewareInterface
{
    public function __invoke(
        Message\ServerRequestInterface $request,
        MiddlewareInterface $next = null
    );
}
