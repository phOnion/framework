<?php
namespace Onion\Framework\State\Interfaces;

interface TransitionInterface
{
    public function getSource(): string;
    public function getDestination(): string;
    public function getHandler(): ?callable;

    public function hasHandler(): bool;
}
