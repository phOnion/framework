<?php
declare(strict_types=1);
namespace Onion\Framework\Dependency\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerErrorException extends \Exception implements ContainerExceptionInterface
{
}
