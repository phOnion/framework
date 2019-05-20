<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoricalFlowInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Psr\SimpleCache\CacheInterface;

class CacheableFlow implements HistoricalFlowInterface
{
    private $wrapped;
    private $cache;

    public function __construct(HistoricalFlowInterface $wrapped, CacheInterface $cache)
    {
        $this->wrapped = $wrapped;
        $this->cache = $cache;
    }

    public function addTransition(TransitionInterface $transition): void
    {
        $this->wrapped->addTransition($transition);
    }

    public function apply(string $state, object $target, ...$arguments): bool
    {
        return $this->wrapped->apply($state, $target, ...$arguments);
    }

    public function reset(): void
    {
        $this->wrapped->reset();
    }

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    public function getState(): string
    {
        return $this->wrapped->getState();
    }

    public function reply(): void
    {
        $this->wrapped->reply();
    }

    public function __destruct()
    {
        $this->cache->set($this->getName(), $this->wrapped->getHistory());
    }
}
