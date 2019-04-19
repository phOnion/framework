<?php
namespace Tests\Http\Emitter;

use GuzzleHttp\Stream\StreamInterface;
use Onion\Framework\Http\Emitter\StreamEmitter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface as HttpStream;

class StreamEmitterTest extends TestCase
{
    public function testEmitting()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->write("HTTP/1.1 200 OK\r\n")->shouldBeCalledOnce();
        $stream->write("accept: text/plain\r\n")->shouldBeCalledOnce();
        $stream->write("accept: text/html\r\n")->shouldBeCalledOnce();
        $stream->write("x-custom: foo\r\n")->shouldBeCalledOnce();
        $stream->write("Hello, World\r\n")->shouldBeCalledOnce();
        $stream->write("\r\n")->shouldBeCalledTimes(2);

        $body = $this->prophesize(HttpStream::class);
        $body->getContents()->willReturn('Hello, World');

        $response = $this->prophesize(ResponseInterface::class);
        $response->getProtocolVersion()
            ->willReturn('1.1')
            ->shouldBeCalledOnce();
        $response->getStatusCode()
            ->willReturn(200)
            ->shouldBeCalledOnce();
        $response->getReasonPhrase()
            ->willReturn('OK')
            ->shouldBeCalledOnce();
        $response->getHeaders()
            ->willReturn([
                'accept' => [
                    'text/plain',
                    'text/html',
                ],
                'x-custom' => [
                    'foo',
                ]
            ])->shouldBeCalledOnce();
        $response->getBody()
            ->willReturn($body->reveal())
            ->shouldBeCalledOnce();


        $emitter = new StreamEmitter($stream->reveal());
        $emitter->emit($response->reveal());
    }
}
