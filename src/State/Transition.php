<?php
namespace Onion\Framework\State;

use Onion\Framework\State\Interfaces\TransitionInterface;

class Transition implements TransitionInterface
{
    private $source;
    private $destination;

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

    public function hasHandler(): bool
    {
        return $this->handler === null;
    }
}
