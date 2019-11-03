<?php

declare(strict_types=1);

namespace Onion\Framework\State\Interfaces;

interface ReplyableFlowInterface extends FlowInterface
{
    public function reply(): void;
}
