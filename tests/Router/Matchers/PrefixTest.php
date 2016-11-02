<?php
namespace Tests\Router\Matchers;

use Onion\Framework\Router\Matchers\Prefix;

class PrefixTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $matcher = new Prefix();
        $this->assertSame([], $matcher->match('/prefix', '/prefix/yes'));
        $this->assertSame([false], $matcher->match('/prefix', '/test'));
    }
}
