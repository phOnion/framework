<?php

declare(strict_types=1);

namespace Onion\Framework\Http\Emitter\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface EmitterInterface
{
    public function emit(ResponseInterface $response): void;
}
