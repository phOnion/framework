<?php

declare(strict_types=1);

namespace Onion\Framework\State;

use Onion\Framework\State\Exceptions\TransitionException;
use Onion\Framework\State\Interfaces\RepeatableFlowInterface;

class RepeatableFlow extends Flow implements RepeatableFlowInterface
{
    public function reply(object $target): void
    {
        $flow = $this->reset();
        $history = $this->history;
        foreach (($history ?? []) as $index => $transition) {
            [$from, $to, $arguments] = $transition;

            if (!$flow->apply($to, $target, ...$arguments)) {
                throw new TransitionException(
                    "Transition #{$index}: '{$from}' to '{$to}' did not succeed",
                    $history
                );
            }
        }
    }
}
