<?php

declare(strict_types=1);

namespace Onion\Framework\State\Exceptions;

use Onion\Framework\State\Interfaces\HistoryInterface;

class TransitionException extends \Exception
{
    private $history = [];
    public function __construct(string $message, HistoryInterface $history, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->history = $history;
    }

    public function getHistory(): HistoryInterface
    {
        return $this->history;
    }
}
