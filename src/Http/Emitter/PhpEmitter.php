<?php

declare(strict_types=1);

namespace Onion\Framework\Http\Emitter;

use GuzzleHttp\Psr7\Utils;
use Onion\Framework\Http\Emitter\Interfaces\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class PhpEmitter implements EmitterInterface
{
    public function emit(ResponseInterface $response): void
    {
        \header(
            "HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}",
            true,
            $response->getStatusCode()
        );

        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $idx => $value) {
                \header("{$header}: {$value}", $idx === 0);
            }
        }

        echo Utils::copyToString($response->getBody());
    }
}
