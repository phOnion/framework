<?php declare(strict_types=1);
namespace Onion\Framework\State\Interfaces;

interface TransitionInterface
{
    public function getSource(): string;
    public function getDestination(): string;
    public function getHandler(): callable;
    public function getArguments(): array;

    public function withArguments(...$arguments): self;

    public function hasHandler(): bool;

    public function __invoke(): bool;
}
