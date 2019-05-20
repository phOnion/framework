<?php
namespace Onion\Framework\State\Exceptions;

class TransitionException extends \RuntimeException
{
    private $history = [];
    public function __construct(string $message, array $history, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->history = $history;
    }

    public function getHistory(): array
    {
        return $this->history;
    }
}
