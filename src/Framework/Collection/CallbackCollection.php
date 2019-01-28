<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

class CallbackCollection extends Collection
{
    /** @var callable */
    private $callback;

    /**
     * @param mixed[]|\Iterator $items
     */
    public function __construct(iterable $items, callable $callback)
    {
        parent::__construct($items);
        $this->callback = $callback;
    }

    public function current()
    {
        return call_user_func($this->callback, parent::current(), $this->key());
    }
}
