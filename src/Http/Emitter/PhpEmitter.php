<?php declare(strict_types=1);
namespace Onion\Framework\Http\Emitter;

use Onion\Framework\Http\Emitter\Interfaces\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
class PhpEmitter implements EmitterInterface
{
    public function emit(ResponseInterface $response): void
    {
        header(
            "HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}",
            true,
            $response->getStatusCode()
        );

        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $index => $value) {
                header("{$header}: {$value}", $index === 0);
            }
        }

        echo $response->getBody();
    }
}
