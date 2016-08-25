<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Router;

use Onion\Framework\Interfaces\Router\ParserInterface;
use Onion\Framework\Router\Parsers\Flat;

class FlatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParserInterface
     */
    protected $parser;
    public function setUp()
    {
        $this->parser = new Flat();
    }

    public function testPathParsing()
    {
        $this->assertSame('localhost/', $this->parser->parse('http://localhost:80/'));
        $this->assertSame('/', $this->parser->parse('/'));
    }

    public function testInvalidParsingException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parse('http://invalid:colon-position.localhost.tld/dummy');
    }

    public function testMatching()
    {
        $this->assertNull($this->parser->match('/', '/'));
    }

    public function testNoMatchException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->match('/', '/test');
    }
}
