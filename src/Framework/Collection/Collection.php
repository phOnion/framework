<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

class Collection implements \Iterator
{
    /** @var \Iterator $items */
    private $items;

    /** @param mixed[]|\Iterator $items */
    public function __construct(iterable $items)
    {
        if (!$items instanceof \Iterator) {
            $items = new \ArrayIterator($items);
        }

        $this->items = $items;
    }

    public function current()
    {
        return $this->items->current();
    }

    public function key()
    {
        return $this->items->key();
    }

    public function next(): void
    {
        $this->items->next();
    }

    public function rewind(): void
    {
        $this->items->rewind();
    }

    public function valid(): bool
    {
        return $this->items->valid();
    }

    /**
     * @deprecated
     * @see self::filter
     */
    public function setFilter(callable $callback): void
    {
        $this->items = new \CallbackFilterIterator($this->items, $callback);
    }

    public function filter(callable $callback): self
    {
        $self = clone $this;
        $self->setFilter($callback);

        return $self;
    }

    public function map(callable $callback): self
    {
        return new self(
            new CallbackCollection($this->items, $callback)
        );
    }

    public function slice(int $start, int $length = -1): self
    {
        return new self(
            new \LimitIterator($this->items, $start, $length)
        );
    }
}
