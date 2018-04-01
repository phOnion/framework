<?php
declare(strict_types=1);
namespace Tests\Http\Header;

class AcceptHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function testWithSingleValueWithoutWeight()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'application/json'
        );

        $this->assertTrue($header->supports('application/json'));
        $this->assertSame($header->getPriority('application/json'), 1.0);
    }

    public function testWithSingleValueWithWeight()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'application/json;q=0.7,application/vnd.foobar+html'
        );

        $this->assertTrue($header->supports('application/vnd.foobar+html'));
        $this->assertTrue($header->supports('application/json'));
        $this->assertSame($header->getPriority('application/json'), 0.7);
    }

    public function testParsingWithMultipleValues()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'application/json,application/hal+json;q=0.2,*/*;q=0.1'
        );

        $this->assertTrue($header->supports('application/json'));
        $this->assertTrue($header->supports('application/hal+json'));
        $this->assertTrue($header->supports('*/*'));
        $this->assertSame($header->getPriority('*/*'), 0.1);
        $this->assertSame($header->getPriority('application/json'), 1.0);
        $this->assertSame($header->getPriority('application/hal+json'), 0.2);
        $this->assertTrue($header->supports('application/xml'));
        $this->assertSame($header->getPriority('application/xml'), 0.1);
    }

    public function testParsingOfMultiValueLanguage()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'en, en-gb;q=0.8, bg;q=0.5'
        );

        $this->assertTrue($header->supports('en'));
        $this->assertTrue($header->supports('en-gb'));
        $this->assertTrue($header->supports('bg'));
        $this->assertFalse($header->supports('es'));
    }

    public function testParsingOfMultiValueAcceptWithMoreThanOneAttribute()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'application/json;q=0.8, text/plain;level=2;q=0.2, application/*;level=3'
        );

        $this->assertTrue($header->supports('application/json'));
        $this->assertTrue($header->supports('text/plain'));
        $this->assertTrue($header->supports('application/*'));
        $this->assertSame($header->getPriority('application/json'), 0.8);
        $this->assertSame($header->getPriority('text/plain'), 0.2);
        $this->assertSame($header->getPriority('application/*'), 1.0);
    }

    public function testParsingOfMultiValueEncoding()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'compress;q=0.5, gzip;q=1.0'
        );
        $this->assertTrue($header->supports('compress'));
        $this->assertTrue($header->supports('gzip'));
        $this->assertFalse($header->supports('zip'));

        $this->assertSame($header->getPriority('compress'), 0.5);
        $this->assertSame($header->getPriority('gzip'), 1.0);
    }

    public function testParsingOfMultiValueCharset()
    {
        $header = new \Onion\Framework\Http\Header\Accept(
            'iso-8895-5, unicode-1-1;q=0.8'
        );

        $this->assertTrue($header->supports('iso-8895-5'));
        $this->assertTrue($header->supports('unicode-1-1'));
        $this->assertSame($header->getPriority('unicode-1-1'), 0.8);
    }
}
