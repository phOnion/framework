<?php declare(strict_types=1);
namespace Onion\Framework\Router\Exceptions;

class MissingHeaderException extends \RuntimeException
{
    private $headerName;

    public function __construct(string $headerName)
    {
        $this->headerName = $headerName;
        parent::__construct("Missing header '{$headerName}'");
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }
}
