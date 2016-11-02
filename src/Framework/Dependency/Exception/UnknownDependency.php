<?php
declare(strict_types=1);
namespace Onion\Framework\Dependency\Exception;

use Interop\Container\Exception\NotFoundException;

class UnknownDependency extends \Exception implements NotFoundException
{
}
