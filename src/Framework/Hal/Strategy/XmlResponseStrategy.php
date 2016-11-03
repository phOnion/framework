<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Hal\Strategy
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Hal\Strategy;

use Onion\Framework\Hal\Link;
use Onion\Framework\Hal\Resource as ResponseResource;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\TextResponse;
use Onion\Framework\Interfaces\Hal\StrategyInterface;

class XmlResponseStrategy implements StrategyInterface
{
    public function getSupportedTypes()
    {
        return ['application/hal+xml', 'application/xml'];
    }

    public function getSupportedExtension()
    {
        return 'xml';
    }

    public function process(ResponseInterface $response)
    {
        /**
         * @var $resource ResponseResource
         */
        $resource = unserialize($response->getBody());

        $xml = new \SimpleXMLElement('<resource></resource>');
        if ($resource->hasLink('self')) {
            $xml->addAttribute('href', $resource->getLink('self')->getHref());
        }

        // Need the DOMDocument, to preserve namespaces of curies :/
        $document = new \DomDocument();
        $this->handleResource($xml, $resource);

        $document->loadXML($xml->saveXML());

        $headers = $response->getHeaders();
        $headers['Content-type'] = 'application/hal+xml';
        return new TextResponse($document->saveXML(), $response->getStatusCode(), $headers);
    }

    /**
     * @param $element \SimpleXMLElement
     * @param $resource ResponseResource
     */
    protected function handleResource($element, $resource)
    {
        if ($resource->getLinks() !== []) {
            foreach ($resource->getLinks() as $link) {
                if (is_array($link)) {
                    foreach ($link as $curie) {
                        $this->handleCurieLinks($element, $curie);
                    }

                    continue;
                }

                $this->handleLink($element, $link);
            }
        }

        $this->processDataArray($element, $resource->getData());
        foreach ($resource->getResources() as $relation => $res) {
            $self = $this;
            array_walk($res, function ($resource) use ($relation, $element, $self) {
                /**
                 * @var $resource ResponseResource
                 */
                $element = $element->addChild('resource');
                $element->addAttribute('rel', $relation);
                if ($resource->hasLink('self')) {
                    $element->addAttribute('href', $resource->getLink('self')->getHref());
                }

                $self->handleResource($element, $resource);
            });
        }
    }

    protected function handleCurieLinks(\SimpleXMLElement $element, Link $link)
    {
        if (($pos = strpos($link->getHref(), '%7B')) !== false) {
            $element->addAttribute('xmlns:xmlns:' . $link->getRel(), substr($link->getHref(), 0, $pos));
            return;
        }

        $element->addAttribute('xmlns:xmlns:' . $link->getRel(), (string)$link->getHref());
    }

    protected function handleLink(\SimpleXMLElement $element, Link $link)
    {
        /**
         * @var $link Link
         */
        $addedLink = $element->addChild('link');
        $addedLink->addAttribute('rel', $link->getRel());
        $addedLink->addAttribute('href', $link->getHref());
        if (!$link->hasType()) {
            $addedLink->addAttribute('type', 'application/hal+xml');
        }

        foreach ($link->getAttributes() as $name => $val) {
            if (in_array($name, ['href', 'ref'], true)) {
                continue;
            }

            $addedLink->addAttribute($name, $val);
        }
    }

    /**
     * @param \SimpleXMLElement $parent
     * @param array      $data
     */
    protected function processDataArray(\SimpleXMLElement $parent, array $data)
    {
        foreach ($data as $el => $value) {
            if (is_array($value)) {
                $e = $parent->addChild($el);

                $this->processDataArray($e, $value);
                continue;
            }
            $parent->addChild($el, $value);
        }
    }
}
