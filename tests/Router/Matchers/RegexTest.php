<?php
namespace Tests\Router\Matchers;

use Onion\Framework\Router\Matchers\Regex;

class RegexTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $matcher = new Regex();
        $this->assertSame(['/'], $matcher->match('/', '/'));
        $this->assertSame([false], $matcher->match('/test', '/'));
    }
}
