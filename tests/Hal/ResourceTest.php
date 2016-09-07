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
use Onion\Framework\Hal\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testResourceBasics()
    {
        $resource = new Resource();
        $this->assertFalse($resource->hasLink('self'));
        $this->assertFalse($resource->getLink('self'));
        $this->assertSame([], $resource->getData());
        $this->assertSame([], $resource->getLinks());
        $this->assertSame([], $resource->getResources());
    }

    public function testResourceAddition()
    {
        $user = new Resource();
        $user->setData(['id' => 1, 'username' => 'test']);
        $profile = new Resource();
        $profile->setData(['firstName' => 'John', 'lastName' => 'Doe']);
        $user->addResource('user', $profile);

        $this->assertSame($user->getData(), ['id' => 1, 'username' => 'test']);
        $this->assertArrayHasKey('user', $user->getResources());
        $this->assertArrayHasKey(0, $user->getResources()['user']);
        $this->assertInstanceOf(Resource::class, $user->getResources()['user'][0]);
    }

    public function testLinkAddition()
    {
        /**
         * @var $link Link
         */
        $link = $this->prophesize(Link::class);
        $link->getRel()->willReturn('self');
        $resource = new Resource();
        $this->assertInstanceOf(Resource::class, $resource->addLink($link->reveal()));
        $this->assertTrue($resource->hasLink('self'));
        $this->assertEquals($link->reveal(), $resource->getLink('self'));

        /**
         * @var $curie Link
         */
        $curie = $this->prophesize(Link::class);
        $curie->getRel()->willReturn('ns');
        $resource->addCurie($curie->reveal());
        $this->assertTrue($resource->hasLink('curies'));
        $this->assertEquals(['ns' => $curie->reveal()], $resource->getLink('curies'));
    }
}
