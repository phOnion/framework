<?php

namespace Tests\Router;

use Onion\Framework\Router\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testUnparameterizedPatternHandling()
    {
        $this->assertSame(
            '/foo/bar',
            (new Parser)->parse('/foo/bar'),
        );
    }

    public function testSimpleParameter()
    {
        $this->assertSame(
            '/foo/(?P<bar>[^/]+)',
            (new Parser())->parse('/foo/{bar}'),
        );
    }

    public function testConstrainedParameter()
    {
        $this->assertSame(
            '/foo/(?P<bar>\d+)',
            (new Parser())->parse('/foo/{bar:\d+}'),
        );
    }

    public function testConditionalParameter()
    {
        $this->assertSame(
            '/foo(?:/(?P<bar>[^/]+))?',
            (new Parser())->parse('/foo/{bar}?'),
        );
    }

    public function testConstrainedConditionalParameter()
    {
        $this->assertSame(
            '/foo(?:/(?P<bar>\d+))?',
            (new Parser())->parse('/foo/{bar:\d+}?'),
        );
    }

    public function testConditionalParameterInMiddle()
    {
        $this->assertSame(
            '/foo(?:/(?P<bar>[^/]+))?(?:/(?P<baz>[^/]+))',
            (new Parser())->parse('/foo/{bar}?/{baz}'),
        );
    }
}
