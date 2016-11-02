<?php
namespace Tests\Router\Matchers;

use Onion\Framework\Router\Matchers\Strict;

class StrictTest extends \PHPUnit_Framework_TestCase
{
    public function testMatching()
    {
        $matcher = new Strict();
        $this->assertSame([], $matcher->match('/', '/'));
        $this->assertSame([false], $matcher->match('/', '/no'));
    }
}
