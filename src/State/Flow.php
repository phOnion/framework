<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;

class Flow implements Interfaces\FlowInterface
{
    /** @var string $name */
    private $name;
    /** @var string $state */
    private $state;
    /** @var string $initialState */
    private $initialState;

    /** @var Transition[] $transitions */
    private $transitions;

    public function __construct(string $name, string $state)
    {
        $this->name = $name;
        $this->state = $this->initialState = $state;
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

    public function getMigrations(): array
    {
        $values = [];
        foreach (array_keys($this->transitions) as $states) {
            if (stripos($states, "{$this->getState()}:") === 0) {
                $values[] = substr($states, strlen($this->getState())+1);
            }
        }

        return $values;
    }

    public function apply(string $state, object $target, ...$arguments): bool
    {
        $key = "{$this->getState()}:{$state}";
        if (!isset($this->transitions[$key])) {
            throw new \LogicException(
                "Moving from '{$this->getState()}' to '{$state}' is not part of the defined flow"
            );
        }

        $handler = $this->transitions[$key]->getHandler();

        if ($handler === null) {
            return true;
        }

        try {
            if (call_user_func($handler, $target, ...$arguments)) {
                $this->state = $state;
                return true;
            }

            return false;
        } catch (\Throwable $ex) {
            throw new \RuntimeException(
                "Transition from '{$this->getState()}' to '{$state}' failed",
                (int) $ex->getCode(),
                $ex
            );
        }

        return false;
    }

    public function reset(): FlowInterface
    {
        return new self($this->getName(), $this->initialState);
    }
}
