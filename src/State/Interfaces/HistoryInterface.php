<?php
namespace Onion\Framework\State\Interfaces;

interface HistoryInterface
{
    public function add(TransitionInterface $transition): void;
}
