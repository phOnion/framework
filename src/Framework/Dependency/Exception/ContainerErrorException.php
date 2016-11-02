<?php
declare(strict_types = 1);
namespace Onion\Framework\Dependency\Exception;

use Interop\Container\Exception\ContainerException;

class ContainerErrorException extends \Exception implements ContainerException, \Throwable
{
}
