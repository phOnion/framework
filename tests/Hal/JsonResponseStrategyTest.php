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
use Onion\Framework\Hal\Strategy\JsonResponseStrategy;
use Onion\Framework\Http\Response\RawResponse;

class JsonResponseStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportedExtensionAndTypes()
    {
        $strategy = new JsonResponseStrategy();
        $this->assertSame('json', $strategy->getSupportedExtension());
        $this->assertContains('application/hal+json', $strategy->getSupportedTypes());
        $this->assertContains('application/json', $strategy->getSupportedTypes());
    }

    public function testDataOnlyResource()
    {
        $resourceResponse = $this->prophesize(RawResponse::class);;
        $self = $this;
        $resourceResponse->getBody()->will(function () use ($self) {
            $resource = $self->prophesize(Resource::class);
            $resource->getData()->willReturn(['id' => 1, 'user' => 'test']);
            $resource->getResources()->willReturn([]);
            $resource->getLinks()->willReturn([]);
            $resource->hasLink('self')->willReturn(false);

            return serialize($resource->reveal());
        });
        $resourceResponse->getStatusCode()->willReturn(200);
        $resourceResponse->getHeaders()->willReturn([]);


        $strategy = new JsonResponseStrategy();
        $response = $strategy->process($resourceResponse->reveal());
        $this->assertSame('{"id":1,"user":"test"}', $response->getBody()->getContents());
    }

    public function testLinkResponse()
    {
        $resourceResponse = $this->prophesize(RawResponse::class);
        $self = $this;
        $resourceResponse->getBody()->will(function() use ($self) {
            $resource = $self->prophesize(Resource::class);
            $selfLink = $self->prophesize(Link::class);

            $selfLink->hasType()->willReturn(false);
            $selfLink->getHref()->willReturn('/resource');
            $selfLink->getRel()->willReturn('self');
            $selfLink->getAttributes()->willReturn(['href' => '/resource']);

            $curieLink = $self->prophesize(Link::class);
            $curieLink->getRel()->willReturn('ns');
            $curieLink->getHref()->willReturn('/rels/{rel}');
            $curieLink->hasType()->willReturn(false);
            $curieLink->getAttributes()->willReturn(['name' => 'ns', 'templated' => true, 'href' => '/rels/{rel}']);

            $extraLink = $self->prophesize(Link::class);
            $extraLink->hasType()->willReturn(false);
            $extraLink->getRel()->willReturn('ns:extra');
            $extraLink->getHref()->willReturn('/resource/{id}/extra');
            $extraLink->getAttributes()->willReturn(['templated' => true, 'deprecated' => true, 'href' => '/resource/{id}/extra']);

            $resource->hasLink('self')->willReturn(true);
            $resource->getLink('self')->willReturn($selfLink->reveal());

            $resource->getLinks()->willReturn([
                'self' => $selfLink->reveal(),
                'extra' => $extraLink->reveal(),
                'curies' => [
                    'ns' => $curieLink->reveal()
                ]
            ]);
            $resource->getData()->willReturn([]);
            $resource->getResources()->willReturn([]);

            return serialize($resource->reveal());
        });
        $resourceResponse->getStatusCode()->willReturn(200);
        $resourceResponse->getHeaders()->willReturn([]);

        $strategy = new JsonResponseStrategy();

        $this->assertSame('{"_links":{"self":{"href":"/resource","type":"application/hal+json"},"ns:extra":{"templated":true,"deprecated":true,"href":"/resource/{id}/extra","type":"application/hal+json"},"curies":[{"name":"ns","templated":true,"href":"/rels/{rel}","type":"application/hal+json"}]}}', $strategy->process($resourceResponse->reveal())->getBody()->getContents());
    }

    public function testEmbeddedResourceResponse()
    {
        $resourceResponse = $this->prophesize(RawResponse::class);
        $resourceResponse->getStatusCode()->willReturn(200);
        $resourceResponse->getHeaders()->willReturn([]);
        $self = $this;
        $resourceResponse->getBody()->will(function () use ($self) {
            $parentSelf = $self->prophesize(Link::class);
            $parentSelf->getRel()->willReturn('self');
            $parentSelf->getHref()->willReturn('/users/1');
            $parentSelf->getAttributes()->willReturn(['href' => '/users/1']);
            $parentSelf->hasType()->willReturn(false);

            $parentResource = $self->prophesize(Resource::class);
            $parentResource->hasLink('self')->willReturn(true);
            $parentResource->getLink('self')->willReturn($parentSelf->reveal());
            $parentResource->getData()->willReturn(['id' => 1, 'user' => 'test']);
            $parentResource->getLinks()->willReturn(['self' => $parentSelf->reveal()]);
//            $parentResource->getRel()->willReturn('user');


            $childSelf = $self->prophesize(Link::class);
            $childSelf->getRel()->willReturn('self');
            $childSelf->getHref()->willReturn('/users/1/profile');
            $childSelf->getAttributes()->willReturn(['href' => '/users/1/profile']);
            $childSelf->hasType()->willReturn(false);
            $childResource = $self->prophesize(Resource::class);
//            $childResource->getRel()->willReturn('profile');
            $childResource->getData()->willReturn(['name' => 'John Doe']);
            $childResource->hasLink('self')->willReturn(true);
            $childResource->getLink('self')->willReturn($childSelf->reveal());
            $childResource->getResources()->willReturn([]);
            $childResource->getLinks()->willReturn(['self' => $childSelf->reveal()]);

            $parentResource->getResources()->willReturn(['profile' => [$childResource->reveal()]]);

            return serialize($parentResource->reveal());
        });

        $strategy = new JsonResponseStrategy();

        $this->assertSame('{"id":1,"user":"test","_embedded":{"profile":[{"name":"John Doe","_links":{"self":{"href":"/users/1/profile","type":"application/hal+json"}}}]},"_links":{"self":{"href":"/users/1","type":"application/hal+json"}}}', $strategy->process($resourceResponse->reveal())->getBody()->getContents());
    }
}
