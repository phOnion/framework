<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\TransitionInterface;

class Transition implements TransitionInterface
{
    private $source;
    private $destination;

    private $arguments = [];

    private $handler;
    private $rollback;

    public function __construct(
        string $source,
        string $destination,
        ?callable $handler = null,
        ?callable $rollback = null
    ) {
        $this->source = strtolower($source);
        $this->destination = strtolower($destination);
        $this->handler = $handler;
        $this->rollback = $rollback ?? function () {
        };
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getHandler(): callable
    {
        return $this->handler;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function withArguments(...$arguments): TransitionInterface
    {
        $self = clone $this;
        $self->arguments = $arguments;

        return $self;
    }

    public function hasHandler(): bool
    {
        return $this->handler !== null;
    }

    public function __invoke(): bool
    {
        if (!$this->hasHandler()) {
            return true;
        }

        return call_user_func(function (...$args) {
            try {
                if (!call_user_func($this->getHandler(), ...$args)) {
                    call_user_func($this->rollback, ...$args);

                    return false;
                }

                return true;
            } catch (\Throwable $ex) {
                $args[] = $ex;

                call_user_func($this->rollback, ...$args);

                return false;
            }

            return false;
        }, ...$this->getArguments());
    }
}
