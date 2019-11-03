<?php

declare(strict_types=1);

namespace Onion\Framework\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Interfaces\FlowInterface;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Onion\Framework\State\Interfaces\ReplyableFlowInterface;
use Onion\Framework\State\Interfaces\TransitionInterface;

class ReplyableFlow implements ReplyableFlowInterface
{
    private $wrapped;

    public function __construct(FlowInterface $flow)
    {
        $this->wrapped = $flow;
    }

    public function apply(string $state, object $target, ...$arguments): bool
    {
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
        $history = $this->getHistory();
        $this->wrapped = $this->reset();
        foreach ($history as $index => $status) {
            /** @var TransitionInterface $status */
            $args = $status->getArguments();
            $target = array_shift($args);

            if (!$this->apply($status->getDestination(), $target, ...$args)) {
                throw new TransitionException(
                    "Transition #{$index}: '{$this->getState()}' to '{$status->getDestination()}' did not succeed",
                    $history
                );
            }
        }
    }

    public function getHistory(): HistoryInterface
    {
        return $this->wrapped->getHistory();
    }
}
