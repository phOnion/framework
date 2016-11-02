<?php
declare(strict_types=1);
namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Router\Interfaces\Exception\NotFoundException as RouteNotFoundException;

class NotFoundException extends \Exception implements RouteNotFoundException
{
}
