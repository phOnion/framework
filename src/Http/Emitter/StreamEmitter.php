<?php
namespace Onion\Framework\Http\Emitter;

use GuzzleHttp\Stream\Stream;
use Onion\Framework\Http\Emitter\Interfaces\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class StreamEmitter implements EmitterInterface
{
    private $stream;

    public function __construct($stream)
    {
        $this->stream = Stream::factory($stream);
    }

    public function emit(ResponseInterface $response): void
    {
        $this->stream->write(
            "HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()} {$response->getReasonPhrase()}\r\n"
        );

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $this->stream->write(
                    "{$name}: {$value}\r\n"
                );
            }
        }

        $this->stream->write("\r\n");
        $this->stream->write("{$response->getBody()->getContents()}\r\n");
        $this->stream->write("\r\n");
    }
}
