<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoricalFlowInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;

class SeekableFlow implements HistoricalFlowInterface
{
    private $wrapped;
    private $history = [];

    public function __construct(FlowInterface $flow)
    {
        $this->wrapped = $flow;
    }

    public function apply(string $state, object $target, ...$arguments): bool
    {
        $this->history[] = [$state, $target, $arguments];

        return $this->wrapped->apply($state, $target, ...$arguments);
    }

    public function reset(): FlowInterface
    {
        return new self($this->wrapped->reset());
    }

    public function getState(): string
    {
        return $this->wrapped->getState();
    }

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    public function can(string $state): bool
    {
        return $this->wrapped->can($state);
    }

    public function addTransition(TransitionInterface $transition): void
    {
        $this->wrapped->addTransition($transition);
    }

    public function reply(): void
    {
        $this->reset();
        foreach ($this->history as $status) {
            list($state, $target, $args)=$status;

            try {
                if (!$this->apply($state, $target, ...$args)) {
                    throw new \RuntimeException(
                        "Transition from '{$this->getState()}' to '{$state}' failed"
                    );
                }
            } catch (\Throwable $ex) {
                throw new \RuntimeException(
                    "Failed to reply transition '{$this->getState()}' -> '{$state}'",
                    (int) $ex->getCode(),
                    $ex
                );
            }
        }
    }

    public function getHistory(): array
    {
        return $this->history;
    }
}
