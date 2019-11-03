<?php

declare(strict_types=1);

namespace Onion\Framework\State\Interfaces;

use Onion\Framework\State\Interfaces\TransitionInterface;

interface FlowInterface
{
    public function addTransition(TransitionInterface $transition): void;
    public function getState(): string;
    public function getName(): string;
    public function getHistory(): HistoryInterface;

    public function apply(string $state, object $target, ...$arguments): bool;
    public function can(string $state): bool;

    public function reset(): FlowInterface;
}
