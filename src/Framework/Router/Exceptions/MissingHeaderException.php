<?php declare(strict_types=1);
namespace Onion\Framework\Router\Exceptions;

class MissingHeaderException extends \RuntimeException
{
    private $header;

    public function __construct(string $headerName, int $code = 0, \Throwable $ex)
    {
        parent::__construct(
            "Missing required header '{$headerName} in response",
            $code,
            $ex
        );
    }
}
