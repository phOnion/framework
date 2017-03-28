<?php
namespace Test\Router\Parsers;

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
        $this->assertSame([false], $this->parser->match('/^\/$/i', 'localhost/'));
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
        $this->assertEquals(
            [
                0 => '/resource/users',
                'identifier' => 'users',
                1 => 'users'
            ],
            $this->parser->match(
                '~' . $this->parser->parse('/resource/[identifier]') . '~x',
                '/resource/users.json'
            )
        );
    }

    public function testCatchAll()
    {
        $this->assertSame('/resource(?:.*)?', $this->parser->parse('/resource*'));
        $this->assertSame('/resource/(?:.*)?', $this->parser->parse('/resource/*'));
        $this->assertSame('/resource/(?:.*)?/(?P<user>\w+)', $this->parser->parse('/resource/*/[user:*]'));
        $this->assertSame(['/resource/some/random/endpoint'], $this->parser->match(
            '~' . $this->parser->parse('/resource/*') . '~x',
            '/resource/some/random/endpoint'
        ));

        $this->assertSame(['/resources/some/random/endpoint'], $this->parser->match(
            '~' . $this->parser->parse('/resource*') . '~x',
            '/resources/some/random/endpoint'
        ));

        $this->assertSame([
            0 => '/resource/some/random/endpoint',
            'name' => 'endpoint',
            1 => 'endpoint'
        ], $this->parser->match(
            '~' . $this->parser->parse('/resource/*/[name:*]') . '~x',
            '/resource/some/random/endpoint'
        ));
    }

    public function testMatchWithWildcard()
    {
        $this->assertSame('/(?:.*)?foo/bar', $this->parser->parse('/*foo/bar'));
        $this->assertSame([
            '/foo/bar'
        ], $this->parser->match(
            '~' . $this->parser->parse('/*foo/bar') . '~x',
            '/foo/bar'
        ));
    }
}
