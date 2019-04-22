<?php declare(strict_types=1);
namespace Onion\Framework\Dependency\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerErrorException
 *
 * @package Onion\Framework\Dependency\Exception
 */
class ContainerErrorException extends \Exception implements ContainerExceptionInterface
{
}
