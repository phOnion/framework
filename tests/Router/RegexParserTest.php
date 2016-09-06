<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Test\Router;

use Onion\Framework\Router\Parsers\Regex;

class RegexParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Regex
     */
    protected $parser;
    public function setUp()
    {
        $this->parser = new Regex();
    }

    public function testWildcards()
    {
        $this->assertSame(
            '/(?P<param>\w)',
            $this->parser->parse('/[param:?]')
        );

        $this->assertSame(
            '/(?P<param>\w+)',
            $this->parser->parse('/[param:*]')
        );
    }

    public function testParserWildcards()
    {
        $this->assertSame(
            '/(?:(?P<action>\w)/(?P<name>\w))?',
            $this->parser->parse('/[[action:\w]/[name:\w]]')
        );
    }

    public function testMatchingWildcards()
    {
        $pattern = '~^' . $this->parser->parse('/[demo:*]') . '$~i';
        $this->assertSame('~^/(?P<demo>\w+)$~i', $pattern);
        $this->assertSame([
            0 => '/test',
            'demo' => 'test',
            1 => 'test'
        ], $this->parser->match(
            $pattern,
            '/test'
        ));
        $this->assertContains(
            'test',
            $this->parser->match($pattern, '/test')
        );
        $this->assertArrayHasKey(
            'demo',
            $this->parser->match($pattern, '/test')
        );
    }

    public function testMatchNoMatchException()
    {
        $this->assertFalse($this->parser->match('/^\/$/i', 'localhost/'));
    }

    public function testMatchOnOptionalGroups()
    {
        $this->assertSame(
            '/strict(?:/(?P<optional>\p{L}+))?',
            $this->parser->parse('/strict[/[optional:\p{L}+]]')
        );
    }

    public function testMatchWhenPathHasFileExtension()
    {
//        $this->assertSame(
//            '/resource/(?:P<identifier>\p{L})',
//            $this->parser->parse('/resource/[identifier]')
//        );
        $this->assertEquals([
            'identifier' => 'users',
            'ext' => 'json',
            0 => '/resource/users.json',
            1 => 'users',
            2 => 'json'
        ],
            $this->parser->match(
                '~' . $this->parser->parse('/resource/[identifier][.[ext]]') . '~x',
                '/resource/users.json'
            )
        );
    }
}
