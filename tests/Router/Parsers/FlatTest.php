<?php
namespace Tests\Router\Parsers;

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
        $this->assertSame('/', $this->parser->parse('/'));
    }
}
