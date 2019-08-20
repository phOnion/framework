<?php declare(strict_types=1);
namespace Onion\Framework\State\Interfaces;

interface HistoryInterface
{
    public function add(TransitionInterface $transition): void;
}
