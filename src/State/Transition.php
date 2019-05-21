<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\TransitionInterface;

class Transition implements TransitionInterface
{
    private $source;
    private $destination;

    private $arguments = [];

    private $handler;

    public function __construct(string $source, string $destination, ?callable $handler = null)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->handler = $handler;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getHandler(): ?callable
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

        return call_user_func($this->getHandler(), ...$this->getArguments());
    }
}
