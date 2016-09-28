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
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\JsonResponse;
use Onion\Framework\Hal\Resource as ResponseResource;
use Onion\Framework\Interfaces\Hal\StrategyInterface;

class JsonResponseStrategy implements StrategyInterface
{
    public function getSupportedTypes()
    {
        return ['application/hal+json', 'application/json'];
    }

    public function getSupportedExtension()
    {
        return 'json';
    }

    public function process(ResponseInterface $data)
    {
        return new JsonResponse(
            $this->handleResource(unserialize((string) $data->getBody())),
            $data->getStatusCode(),
            array_merge($data->getHeaders(), ['content-type' => ['application/hal+json']]),
            JsonResponse::DEFAULT_JSON_FLAGS
        );
    }

    protected function handleResource(ResponseResource $resource)
    {
        $data = $resource->getData();
        $self = $this;

        foreach ($resource->getResources() as $relation => $res) {
            array_walk($res, function ($resource) use ($self, $relation, &$data) {
                $data['_embedded'][$relation][] = $self->handleResource($resource);
            });
        }

        foreach ($resource->getLinks() as $link) {
            if (is_array($link)) {
                /**
                 * @var $link array
                 */
                $self = $this;
                array_walk($link, function (&$value) use ($self) {
                    $value = $self->handleLink($value);
                });
                $data['_links']['curies'] = array_values($link);

                continue;
            }

            $data['_links'][$link->getRel()] = $this->handleLink($link);
        }

        return $data;
    }

    /**
     * @param $link Link
     *
     * @return array
     */
    protected function handleLink(Link $link)
    {
        $data = $link->getAttributes();
        if (!$link->hasType()) {
            $data['type'] = 'application/hal+json';
        }

        return $data;
    }
}
