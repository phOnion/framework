<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;
use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Interfaces\HistoryInterface;

class Flow implements Interfaces\FlowInterface
{
    /** @var string $name */
    private $name;
    /** @var string $state */
    private $state;
    /** @var string $initialState */
    private $initialState;

    /** @var Transition[] $transitions */
    private $transitions = [];

    private $history = [];

    public function __construct(string $name, string $state, HistoryInterface $history = null)
    {
        $this->name = $name;
        $this->state = $this->initialState = $state;
        if ($history === null) {
            $history = new History();
        }
        $this->history = $history;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function addTransition(TransitionInterface $transition): void
    {
        $this->transitions["{$transition->getSource()}:{$transition->getDestination()}"] = $transition;
    }

    public function can(string $state): bool
    {
        return isset($this->transitions["{$this->getState()}:{$state}"]);
    }

    public function getPossibleTransitions(): array
    {
        $values = [];
        foreach (array_keys($this->transitions) as $states) {
            if (stripos($states, "{$this->getState()}:") === 0) {
                $values[] = substr($states, strlen($this->getState()) + 1);
            }
        }

        return $values;
    }

    public function apply(string $state, object $target, ...$arguments): bool
    {
        $target = clone $target;

        $key = "{$this->getState()}:{$state}";

        if (!isset($this->transitions[$key])) {
            $this->getHistory()
                ->add((new Transition($this->getState(), $state))->withArguments($target, ...$arguments));

            throw new TransitionException(
                "Moving from '{$this->getState()}' to '{$state}' is not part of the defined flow",
                $this->getHistory()
            );
        }


        $transition = $this->transitions[$key]->withArguments($target, ...$arguments);
        $this->getHistory()->add($transition);

        try {
            if ($transition($target, ...$arguments)) {
                $this->state = $state;
                return true;
            }

            return false;
        } catch (\Throwable $ex) {
            throw new TransitionException(
                "Transition from '{$this->getState()}' to '{$state}' failed",
                $this->getHistory(),
                $ex
            );
        }

        return false;
    }

    public function reset(): FlowInterface
    {
        $self = new self($this->getName(), $this->initialState);
        array_map([$self, 'addTransition'], $this->transitions);

        return $self;
    }

    public function getHistory(): HistoryInterface
    {
        return $this->history;
    }
}
