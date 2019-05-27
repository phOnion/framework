<?php
namespace Onion\Framework\State\Interfaces;

interface ReplyableFlowInterface extends FlowInterface
{
    public function reply(): void;
}
