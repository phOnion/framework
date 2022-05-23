<?php

namespace Tests\Router;

use Onion\Framework\Router\Collector;
use Onion\Framework\Router\Interfaces\ParserInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CollectorTest extends TestCase
{
    use ProphecyTrait;

    public function testLiteralRouteMatching()
    {
        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/foo')->willReturn('/foo')
            ->shouldBeCalledOnce();

        $collector = new Collector($parser->reveal());
        $collector->add(['GET'], '/foo', fn () => null);

        $this->assertArrayHasKey(
            '/foo(*MARK:1)',
            \iterator_to_array($collector),
        );
        $this->assertCount(1, \iterator_to_array($collector));
    }

    public function testGroupRouteMatching()
    {
        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/test')->willReturn('/test')
            ->shouldBeCalledOnce();
        $parser->parse('/foo')->willReturn('/foo')
            ->shouldBeCalledOnce();

        $collector = new Collector($parser->reveal());
        $collector->group('/test', static function ($collector) {
            $collector->add(['GET'], '/foo', fn () => null);
        });

        $this->assertArrayHasKey(
            '/test/foo(*MARK:1)',
            \iterator_to_array($collector),
        );
        $this->assertCount(1, \iterator_to_array($collector));
    }

    public function testMultipleRouteDefinitions()
    {
        $parser = $this->prophesize(ParserInterface::class);
        $parser->parse('/test')->willReturn('/test')
            ->shouldBeCalledOnce();
        $parser->parse('/foo')->willReturn('/foo')
            ->shouldBeCalledOnce();
        $parser->parse('/bar')->willReturn('/bar')
            ->shouldBeCalledOnce();

        $collector = new Collector($parser->reveal());
        $collector->group('/test', static function ($collector) {
            $collector->add(['GET'], '/foo', fn () => null);
        });
        $collector->add(['GET'], '/bar', fn () => null);

        $this->assertArrayHasKey(
            '/test/foo(*MARK:1)|/bar(*MARK:2)',
            \iterator_to_array($collector),
        );
        $this->assertCount(1, \iterator_to_array($collector));
    }
}
