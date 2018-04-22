<?php declare(strict_types=1);
namespace Onion\Framework\Router\Exceptions;

class MissingHeaderException extends \RuntimeException
{
    public function __construct(string $headerName, int $code = 0, \Throwable $ex = null)
    {
        parent::__construct("Missing required header '{$headerName}' in request", $code, $ex);
    }
}
