<?php

declare(strict_types=1);

namespace Onion\Framework\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;

class Flow implements Interfaces\FlowInterface
{
    private string $state;

    /** @var \Closure[][] $transitions */
    private array $transitions = [];

    public function __construct(
        public readonly string $name,
        public readonly string $initialState,
        public readonly ?HistoryInterface $history = null
    ) {
        $this->state = $initialState;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getBranches(): ?array
    {
        return isset($this->transitions[$this->state]) ? \array_keys($this->transitions[$this->state]) : null;
    }

    public function addTransition(string $from, string $to, \Closure $transition): void
    {
        if (!isset($this->transitions[$from])) {
            $this->transitions[$from] = [];
        }

        $this->transitions[$from][$to] = $transition;
    }

    public function can(string $state): bool
    {
        return isset($this->transitions[$this->getState()][$state]);
    }

    public function apply(string $state, object $target, mixed ...$arguments): bool
    {
        $transition = $this->transitions[$this->state][$state] ?? null;
        $this->history?->add($this->state, $state, $arguments);

        if ($transition === null) {
            throw new TransitionException(
                "Moving from '{$this->getState()}' to '{$state}' is not part of the defined flow",
                $this->history
            );
        }


        try {
            if ($transition($target, ...$arguments)) {
                $this->state = $state;

                return true;
            }

            return false;
        } catch (\Throwable $ex) {
            throw new TransitionException(
                "Transition from '{$this->state}' to '{$state}' failed",
                $this->history,
                $ex
            );
        }
    }

    public function reset(): FlowInterface
    {
        $self = new Flow($this->name, $this->initialState);
        foreach ($this->transitions as $from => $destinations) {
            foreach ($destinations as $to => $handler) {
                $self->addTransition($from, $to, $handler);
            }
        }


        return $self;
    }
}
