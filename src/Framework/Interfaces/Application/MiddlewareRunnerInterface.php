<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Interfaces\Application
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces\Application;

use Onion\Framework\Interfaces\MiddlewareInterface;

interface MiddlewareRunnerInterface extends MiddlewareInterface
{
    public function addMiddleware(array $middleware);
}
