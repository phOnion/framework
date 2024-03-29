<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Class UnknownDependency
 *
 * @package Onion\Framework\Dependency\Exception
 */
class UnknownDependencyException extends ContainerErrorException implements NotFoundExceptionInterface
{
}
