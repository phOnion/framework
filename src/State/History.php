<?php

declare(strict_types=1);

namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\HistoryInterface;

use function Onion\Framework\Common\generator;

class History implements \IteratorAggregate, HistoryInterface, \Countable
{
    private $transitions = [];

    public function add(string $from, string $to, array $arguments): void
    {
        $this->transitions[] = [
            $from, $to, $arguments
        ];
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->transitions);
    }

    public function count(): int
    {
        return count($this->transitions);
    }
}
