<?php
/**
 * PHP Version 5.6.0
 *
 * @category Errors
 * @package  Onion\Framework\Dependency\Exception
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Dependency\Exception;

use Interop\Container\Exception\NotFoundException;

class UnknownDependency extends \Exception implements NotFoundException
{

}
