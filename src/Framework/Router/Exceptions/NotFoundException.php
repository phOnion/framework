<?php
/**
 * PHP Version 5.6.0
 *
 * @category Errors
 * @package  Onion\Framework\Router\Exceptions
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Interfaces\Router\Exception\NotFoundException as RouteNotFoundException;

class NotFoundException extends \Exception implements RouteNotFoundException
{
}
