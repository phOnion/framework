<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Hal
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Hal;


use Onion\Framework\Hal\Link;
use Psr\Http\Message\UriInterface;

class LinkTest extends \PHPUnit_Framework_TestCase
{
    public function testLink()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->__toString()->willReturn('/');
        $link = new Link('self', $uri->reveal(), ['templated' => true]);

        $this->assertSame('self', $link->getRel());
        $this->assertSame('/', $link->getHref());
        $this->assertSame(['templated' => true, 'href' => '/'], $link->getAttributes());
        $this->assertFalse($link->hasType());

        $link = new Link('self', $uri->reveal(), ['type' => 'text/html']);
        $this->assertSame(['type' => 'text/html', 'href' => '/'], $link->getAttributes());
    }
}
