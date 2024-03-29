<?php

declare(strict_types=1);

namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\HistoryInterface;

/**
 * @template-implements \IteratorAggregate<int, array{string, string, array<mixed>}>
 */
class History implements \IteratorAggregate, HistoryInterface, \Countable
{
    /**
     * @var array<int, array{string, string, array<mixed>}>
     */
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
        return \count($this->transitions);
    }
}
