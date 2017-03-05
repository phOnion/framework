<?php
declare(strict_types=1);
namespace Onion\Framework\Dependency\Exception;

use Psr\Container\NotFoundExceptionInterface;

class UnknownDependency extends \Exception implements NotFoundExceptionInterface
{
}
