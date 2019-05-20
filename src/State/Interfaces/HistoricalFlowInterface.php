<?php
namespace Onion\Framework\State\Interfaces;

interface HistoricalFlowInterface extends FlowInterface
{
    public function reply(): void;
    public function getHistory(): array;
}
